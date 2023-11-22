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

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tobento\Service\Queue\Parameter;
use Throwable;

/**
 * FailedJobHandler
 */
class FailedJobHandler implements FailedJobHandlerInterface
{
    /**
     * The log level used for the logger.
     */
    protected const LOG_LEVEL = LogLevel::ERROR;
    
    /**
     * Create a new FailedJobHandler.
     *
     * @param null|QueuesInterface $queues
     * @param null|LoggerInterface $logger
     */
    public function __construct(
        protected null|QueuesInterface $queues = null,
        protected null|LoggerInterface $logger = null,
    ) {}
    
    /**
     * Handle the failed job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    public function handleFailedJob(JobInterface $job, null|Throwable $e = null): void
    {
        $reason = $job->parameters()->get(Parameter\Failed::class)?->reason();
        
        match (true) {
            $reason === Parameter\Failed::TIMED_OUT => $this->handleTimedOut($job, $e),
            $reason === Parameter\Failed::TIMEOUT_LIMIT => $this->handleTimeoutLimit($job, $e),
            $reason === Parameter\Failed::UNIQUE => $this->handleUnique($job, $e),
            default => $this->handleFailed($job, $e),
        };
    }
    
    /**
     * Handle jobs that are finally failed.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function finallyFailed(JobInterface $job, null|Throwable $e): void
    {
        $reason = $job->parameters()->get(Parameter\Failed::class)?->reason();
        
        if (!is_null($e)) {
            $this->logJob($e->getMessage(), $job, $e);
            return;
        }
        
        $message = match (true) {
            $reason === Parameter\Failed::TIMED_OUT => 'Timed out',
            $reason === Parameter\Failed::TIMEOUT_LIMIT => 'Timeout limit',
            $reason === Parameter\Failed::UNIQUE => 'Unique',
            default => 'Unknown Reason',
        };
        
        $this->logJob($message, $job, $e);
    }
    
    /**
     * Handle failed job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function handleFailed(JobInterface $job, null|Throwable $e): void
    {
        $this->retryJob($job, $e);
    }

    /**
     * Handle timed out job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function handleTimedOut(JobInterface $job, null|Throwable $e): void
    {
        $this->retryJob($job, $e);
    }
    
    /**
     * Handle timeout limit job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function handleTimeoutLimit(JobInterface $job, null|Throwable $e): void
    {
        if (! $job->parameters()->has(Parameter\Retry::class)) {
            $job->parameter(new Parameter\Retry(max: 3));
        }
        
        $this->retryJob($job, $e);
    }
    
    /**
     * Handle unique job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function handleUnique(JobInterface $job, null|Throwable $e): void
    {
        // we do nothing as the unique parameter repushes the job with a delay!
    }
    
    /**
     * Retry the job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function retryJob(JobInterface $job, null|Throwable $e): void
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
     * @param null|Throwable $e
     * @return void
     */
    protected function repushJob(JobInterface $job, null|Throwable $e): void
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
    
    /**
     * Logs the job.
     *
     * @param string $message
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function logJob(string $message, JobInterface $job, null|Throwable $e): void
    {
        if (is_null($this->logger)) {
            return;
        }

        $this->logger->log(
            static::LOG_LEVEL,
            sprintf('Job %s with the id %s failed: %s', $job->getName(), $job->getId(), $message),
            [
                'name' => $job->getName(),
                'id' => $job->getId(),
                'payload' => $job->getPayload(),
                'parameters' => $job->parameters()->jsonSerialize(),
                'exception' => $e,
            ]
        );
    }
}