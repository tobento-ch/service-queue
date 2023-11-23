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

namespace Tobento\Service\Queue\Parameter;

use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\JobSkipException;
use Tobento\Service\Queue\JobException;
use Psr\SimpleCache\CacheInterface;
use JsonSerializable;
use Throwable;

/**
 * Unique.
 */
class Unique extends Parameter implements JsonSerializable, Processable, Failable
{
    /**
     * Create a new Unique.
     *
     * @param null|string $id A unique id. If null it uses the job id.
     * @param int $delayInSeconds The job delay in seconds as fallback if job has no duration parameter.
     */
    public function __construct(
        protected null|string $id = null,
        protected int $delayInSeconds = 30,
    ) {}
    
    /**
     * Returns the unique id.
     *
     * @return null|string
     */
    public function id(): null|string
    {
        return $this->id;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id()];
    }
    
    /**
     * Returns the before process job handler.
     *
     * @return null|callable
     */
    public function getBeforeProcessJobHandler(): null|callable
    {
        return [$this, 'beforeProcessJob'];
    }
    
    /**
     * Returns the after process job handler.
     *
     * @return null|callable
     */
    public function getAfterProcessJobHandler(): null|callable
    {
        return [$this, 'afterProcessJob'];
    }
    
    /**
     * Returns the failed job handler.
     *
     * @return callable
     */
    public function getFailedJobHandler(): callable
    {
        return [$this, 'processFailedJob'];
    }
    
    /**
     * Before process job handler.
     *
     * @param JobInterface $job
     * @param CacheInterface $cache
     * @param QueuesInterface $queues
     * @return JobInterface
     * @throws \Throwable
     */
    public function beforeProcessJob(JobInterface $job, CacheInterface $cache, QueuesInterface $queues): JobInterface
    {
        if ($cache->has($this->getJobCacheKey($job))) {
            
            // If job is processing we simply delay the job
            // based on the duration and repush it to the queue:
            
            $durationInSeconds = $job->parameters()->get(Duration::class)?->seconds();
            $queueName = $job->parameters()->get(Queue::class)?->name();
            
            if (is_null($queueName)) {
                throw new JobException($job, 'No queue name specified to process unique job');
            }
            
            $job->parameter(new Delay(
                seconds: is_null($durationInSeconds) ? $this->delayInSeconds : $durationInSeconds
            ));
            
            $queues->get($queueName)?->push($job);
            
            throw new JobSkipException(
                job: $job,
                message: 'Job running in another process',
                retry: false, // set to false as we repushed the job above with a delay
            );
        }
        
        // add to cache so we can determine if the job is processing:
        $cache->set(key: $this->getJobCacheKey($job), value: true);
        
        return $job;
    }
    
    /**
     * After process job handler.
     *
     * @param JobInterface $job
     * @param CacheInterface $cache
     * @return JobInterface
     */
    public function afterProcessJob(JobInterface $job, CacheInterface $cache): JobInterface
    {
        $cache->delete($this->getJobCacheKey($job));
        
        return $job;
    }
    
    /**
     * Process failed job.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @param CacheInterface $cache
     * @return void
     */
    public function processFailedJob(JobInterface $job, Throwable $e, CacheInterface $cache): void
    {
        $cache->delete($this->getJobCacheKey($job));
    }
    
    /**
     * Returns the cache key for the specified job.
     *
     * @param JobInterface $job
     * @return string
     */
    protected function getJobCacheKey(JobInterface $job): string
    {
        $uniqueId = $this->id() ?: $job->getId();
        
        return 'job-processing:'.$uniqueId;
    }
}