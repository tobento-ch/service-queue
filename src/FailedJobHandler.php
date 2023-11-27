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

use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\JobSkipException;
use Throwable;

/**
 * FailedJobHandler
 */
class FailedJobHandler implements FailedJobHandlerInterface
{
    /**
     * Create a new FailedJobHandler.
     *
     * @param null|QueuesInterface $queues
     */
    public function __construct(
        protected null|QueuesInterface $queues = null,
    ) {}
    
    /**
     * Handle the failed job.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     */
    public function handleFailedJob(JobInterface $job, Throwable $e): void
    {
        match (true) {
            $e instanceof JobSkipException => $this->handleSkippedJob($job, $e),
            default => $this->handleFailed($job, $e),
        };
    }
    
    /**
     * Handle exception thrown by the worker e.g.
     *
     * @param Throwable $e
     * @return void
     */
    public function handleException(Throwable $e): void
    {
        //
    }
    
    /**
     * Handle jobs that are finally failed.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     */
    protected function finallyFailed(JobInterface $job, Throwable $e): void
    {
        //
    }
    
    /**
     * Handle failed job.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     */
    protected function handleFailed(JobInterface $job, Throwable $e): void
    {
        $this->retryJob($job, $e);
    }

    /**
     * Handle skipped job.
     *
     * @param JobInterface $job
     * @param JobSkipException $e
     * @return void
     */
    protected function handleSkippedJob(JobInterface $job, JobSkipException $e): void
    {
        if (! $e->retry()) {
            return;
        }
        
        if (! $job->parameters()->has(Parameter\Retry::class)) {
            $job->parameter(new Parameter\Retry(max: 3));
        }
        
        $this->retryJob($job, $e);
    }
    
    /**
     * Retry the job.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     */
    protected function retryJob(JobInterface $job, Throwable $e): void
    {
        $retry = $job->parameters()->get(Parameter\Retry::class);
        
        if (is_null($retry)) {
            $this->finallyFailed($job, $e);
            return;
        }
        
        if ($retry->isMaxReached()) {
            $this->finallyFailed($job, $e);
            return;
        }
        
        $retry->increment();
        
        $this->repushJob($job, $e);
    }
    
    /**
     * Repush the job.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     */
    protected function repushJob(JobInterface $job, Throwable $e): void
    {
        $queue = $job->parameters()->get(Parameter\Queue::class);
                
        if (
            !is_null($queue)
            && !is_null($this->queues)
            && $this->queues->has(name: $queue->name())
        ) {
            $this->queues->queue(name: $queue->name())->push($job);
            return;
        }
        
        $this->finallyFailed($job, $e);
    }
}