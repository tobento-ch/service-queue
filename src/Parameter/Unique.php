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
class Unique extends Parameter implements JsonSerializable, Pushable, Processable, Failable
{
    /**
     * Create a new Unique.
     *
     * @param null|string $id A unique id. If null it uses the job id.
     */
    public function __construct(
        protected null|string $id = null,
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
     * Returns the pushing job handler.
     *
     * @return callable
     */
    public function getPushingJobHandler(): callable
    {
        return [$this, 'pushingJob'];
    }
    
    /**
     * Returns the before process job handler.
     *
     * @return null|callable
     */
    public function getBeforeProcessJobHandler(): null|callable
    {
        return null;
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
     * Pushing job.
     *
     * @param JobInterface $job
     * @param CacheInterface $cache
     * @return JobInterface
     */
    public function pushingJob(JobInterface $job, CacheInterface $cache): JobInterface
    {
        if ($cache->has($this->getJobCacheKey($job))) {
            throw new JobSkipException(
                job: $job,
                message: 'Job already queued',
                retry: false,
            );
        }
        
        // add to cache so we can determine if the job is already queued:
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
    public function getJobCacheKey(JobInterface $job): string
    {
        $uniqueId = $this->id() ?: $job->getId();
        
        return 'job-unique:'.$uniqueId;
    }
}