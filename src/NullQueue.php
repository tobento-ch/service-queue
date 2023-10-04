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
 * NullQueue: does not queue any job and therefore jobs will not be processed at all.
 */
final class NullQueue implements QueueInterface
{
    /**
     * Create a new NullQueue.
     *
     * @param string $name
     * @param int $priority
     */
    public function __construct(
        private string $name,
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
        return null;
    }
    
    /**
     * Returns the job or null if not found.
     *
     * @param string $id The job id.
     * @return null|JobInterface
     */
    public function getJob(string $id): null|JobInterface
    {
        return null;
    }
    
    /**
     * Returns all jobs.
     *
     * @return iterable<int|string, JobInterface>
     */
    public function getAllJobs(): iterable
    {
        return [];
    }
    
    /**
     * Returns the number of jobs in queue.
     *
     * @return int
     */
    public function size(): int
    {
        return 0;
    }
    
    /**
     * Deletes all jobs from the queue.
     *
     * @return bool True if the queue was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        return true;
    }
}