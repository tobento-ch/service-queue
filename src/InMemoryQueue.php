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

use Throwable;

/**
 * InMemoryQueue: stores jobs in memory.
 */
final class InMemoryQueue implements QueueInterface
{
    /**
     * @var array<int, array<string, JobInterface>>
     */
    private array $jobs = [];
    
    /**
     * Create a new InMemoryQueue.
     *
     * @param string $name
     * @param JobProcessorInterface $jobProcessor
     * @param int $priority
     */
    public function __construct(
        private string $name,
        private JobProcessorInterface $jobProcessor,
        private int $priority = 100,
    ) {}
    
    /**
     * Returns the queue name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the queue priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority;
    }
    
    /**
     * Push a new job onto the queue.
     *
     * @param JobInterface $job
     * @return string The job id
     */
    public function push(JobInterface $job): string
    {
        $job = $this->jobProcessor->processPushingJob($job, $this);
        
        $priority = $job->parameters()->get(Parameter\Priority::class)?->priority();
        $priority = is_int($priority) ? $priority : 0;
        
        $this->jobs[$priority][$job->getId()] = $job;
        
        return $job->getId();
    }
    
    /**
     * Pop the next job off of the queue.
     *
     * @return null|JobInterface
     * @throws \Throwable
     */
    public function pop(): null|JobInterface
    {
        ksort($this->jobs);
        
        $priority = array_key_last($this->jobs);
        
        if (is_null($priority)) {
            return null;
        }
        
        $jobs = $this->jobs[$priority];      
        
        $firstKey = array_key_first($jobs);
        
        if (is_null($firstKey)) {
            return null;
        }
        
        $job = $jobs[$firstKey];
        unset($jobs[$firstKey]);
        
        if (empty($jobs)) {
            unset($this->jobs[$priority]);
        } else {
            $this->jobs[$priority] = $jobs;
        }
        
        return $this->jobProcessor->processPoppingJob($job, $this);
    }
    
    /**
     * Returns the job or null if not found.
     *
     * @param string $id The job id.
     * @return null|JobInterface
     */
    public function getJob(string $id): null|JobInterface
    {
        foreach($this->jobs as $jobs) {
            if (isset($jobs[$id])) {
                return $jobs[$id];
            }
        }
        
        return null;
    }
    
    /**
     * Returns all jobs.
     *
     * @return iterable<int|string, JobInterface>
     */
    public function getAllJobs(): iterable
    {
        return array_merge(...$this->jobs);
    }
    
    /**
     * Returns the number of jobs in queue.
     *
     * @return int
     */
    public function size(): int
    {
        return count(array_merge(...$this->jobs));
    }
    
    /**
     * Deletes all jobs from the queue.
     *
     * @return bool True if the queue was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        $this->jobs = [];
        
        return true;
    }
}