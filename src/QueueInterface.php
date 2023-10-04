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

/**
 * QueueInterface
 */
interface QueueInterface
{
    /**
     * Returns the queue name.
     *
     * @return string
     */
    public function name(): string;
    
    /**
     * Returns the queue priority.
     *
     * @return int
     */
    public function priority(): int;
    
    /**
     * Push a new job onto the queue.
     *
     * @param JobInterface $job
     * @return string The job id
     */
    public function push(JobInterface $job): string;
    
    /**
     * Pop the next job off of the queue.
     *
     * @return null|JobInterface
     * @throws \Throwable
     */
    public function pop(): null|JobInterface;
        
    /**
     * Returns the job or null if not found.
     *
     * @param string $id The job id.
     * @return null|JobInterface
     */
    public function getJob(string $id): null|JobInterface;
    
    /**
     * Returns all jobs.
     *
     * @return iterable<int|string, JobInterface>
     */
    public function getAllJobs(): iterable;
    
    /**
     * Returns the number of jobs in queue.
     *
     * @return int
     */
    public function size(): int;
    
    /**
     * Deletes all jobs from the queue.
     *
     * @return bool True if the queue was successfully cleared. False if there was an error.
     */
    public function clear(): bool;
}