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
     * Handle failed job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    protected function handleFailed(JobInterface $job, null|Throwable $e): void
    {
        $this->logJob('Unknown Reason', $job, $e);
        
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
        $this->logJob('Timed out', $job, $e);
        
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
        $this->logJob('Timeout limit', $job, $e);
        
        $this->repushJob($job, $e);
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
        $this->logJob('Unique', $job, $e);
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
            return;
        }
        
        if ($retry->isMaxReached()) {
            $this->logJob('Max retries reached', $job, $e);
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
        
        if (is_null($queue)) {
            $this->logJob('Missing queue to repush job', $job, $e);
            return;
        }
                
        if (!is_null($this->queues)) {
            if ($this->queues->has(name: $queue->name())) {
                $this->queues->queue(name: $queue->name())->push($job);
                $this->logJob('Repushed job', $job, $e);
            } else {
                $this->logJob('Missing queue to repush job', $job, $e);
            }
        }
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