<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\Service\Queue;

use Tobento\Service\Queue\Event;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

/**
 * Worker
 */
class Worker
{
    public const STATUS_SUCCESS = 0;
    public const STATUS_ERROR = 1;
    
    /**
     * Create a new Worker.
     *
     * @param QueuesInterface $queues
     * @param JobProcessorInterface $jobProcessor
     * @param null|FailedJobHandlerInterface $failedJobHandler
     * @param null|EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        protected QueuesInterface $queues,
        protected JobProcessorInterface $jobProcessor,
        protected null|FailedJobHandlerInterface $failedJobHandler = null,
        protected null|EventDispatcherInterface $eventDispatcher = null,
    ) {}

    /**
     * Returns the event dispatcher or null if none.
     *
     * @return null|EventDispatcherInterface
     */
    public function eventDispatcher(): null|EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }
    
    /**
     * Run the queue(s).
     *
     * @param null|string $queue The queue name.
     * @param WorkerOptions $options
     * @return int The status (exit code)
     */
    public function run(null|string $queue, WorkerOptions $options): int
    {
        $this->eventDispatcher?->dispatch(new Event\WorkerStarting($queue, $options));
        
        $startTime = hrtime(true);
        $jobsProcessed = 0;
        
        while (true) {
            
            $secondsRemaining = $options->timeout() - ((hrtime(true) - $startTime) / 1e+6 / 1000);
            
            $job = $this->getNextJob($queue);
            
            if ($job) {
                if ($this->canRunJob($job, $options, $secondsRemaining)) {
                    $jobsProcessed++;
                    $this->runJob($job);
                }
            } else {
                sleep($options->sleep());
            }
            
            $status = $this->stopIfNecessary($options, $secondsRemaining, $jobsProcessed, $job);

            if (!is_null($status)) {
                return $this->stop($status, $queue, $options);
            }
        }
    }
    
    /**
     * Stop the worker.
     *
     * @param int $status
     * @param null|string $queue The queue name.
     * @param WorkerOptions $options
     * @return int The status (exit code)
     */
    protected function stop(int $status, null|string $queue, WorkerOptions $options): int
    {
        $this->eventDispatcher?->dispatch(new Event\WorkerStopped($status, $queue, $options));
        
        return $status;
    }

    /**
     * Returns the next job or null if none.
     *
     * @param string $queue
     * @return null|JobInterface
     */
    protected function getNextJob(null|string $queue = null): null|JobInterface
    {
        try {
            if (is_null($queue) && $this->queues instanceof QueueInterface) {
                return $this->queues->pop();
            }

            return $this->queues->get(name: (string)$queue)?->pop();
        } catch (Throwable $e) {
            $this->eventDispatcher?->dispatch(new Event\PoppingJobFailed($e, $queue));
            return null;
        }
    }

    /**
     * Determine if the job can run.
     *
     * @param JobInterface $job
     * @param WorkerOptions $options
     * @param int|float $secondsRemaining
     * @return bool
     */
    protected function canRunJob(
        JobInterface $job,
        WorkerOptions $options,
        int|float $secondsRemaining,
    ): bool {
        
        $job->parameter(new Parameter\SecondsBeforeTimingOut(seconds: $secondsRemaining));
        $job->parameter(new Parameter\Monitor());
        
        try {
            $this->jobProcessor->beforeProcessJob($job);
            return true;
        } catch (Throwable $e) {
            $this->failedJobHandler?->handleFailedJob($job, $e);
            return false;
        }
    }
    
    /**
     * Run the job.
     *
     * @param JobInterface $job
     * @return void
     */
    protected function runJob(JobInterface $job): void
    {
        $this->eventDispatcher?->dispatch(new Event\JobStarting($job));
        
        try {
            $this->jobProcessor->processJob($job);
            $job = $this->jobProcessor->afterProcessJob($job);
        } catch (Throwable $e) {
            $this->jobProcessor->processFailedJob($job, $e);
            $this->failedJobHandler?->handleFailedJob($job, $e);
            $this->eventDispatcher?->dispatch(new Event\JobFailed($job, $e));
            return;
        }
        
        $this->eventDispatcher?->dispatch(new Event\JobFinished($job));
    }
    
    /**
     * Determine the exit code to stop the process if necessary.
     *
     * @param WorkerOptions $options
     * @param int|float $secondsRemaining
     * @param int $jobsProcessed
     * @param null|JobInterface $job
     * @return int|null Status code to stop worker, otherwise null.
     */
    protected function stopIfNecessary(
        WorkerOptions $options,
        int|float $secondsRemaining,
        int $jobsProcessed = 0,
        null|JobInterface $job = null,
    ): null|int {
        return match (true) {
            (memory_get_usage(true) / 1024 / 1024) >= $options->memory() => static::STATUS_SUCCESS,
            $options->stopWhenEmpty() && is_null($job) => static::STATUS_SUCCESS,
            $secondsRemaining <= 0 => static::STATUS_SUCCESS,
            $options->maxJobs() > 0 && $jobsProcessed >= $options->maxJobs() => static::STATUS_SUCCESS,
            default => null,
        };
    }
}