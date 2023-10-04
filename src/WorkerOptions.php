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

class WorkerOptions
{
    /**
     * Create a new WorkerOptions.
     *
     * @param string $name The name of the worker.
     * @param int $memory The maximum amount of RAM the worker may consume.
     * @param int $timeout The maximum number of seconds a worker may run.
     * @param positive-int $sleep The number of seconds to wait in between polling the queue.
     * @param int $maxJobs The maximum number of jobs to run, 0 (unlimited).
     * @param bool $stopWhenEmpty Indicates if the worker should stop when the queue is empty.
     */
    public function __construct(
        protected string $name = 'default',
        protected int $memory = 128,
        protected int $timeout = 60,
        protected int $sleep = 3,
        protected int $maxJobs = 0,
        protected bool $stopWhenEmpty = false,
    ) {}
    
    /**
     * Returns the name of the worker.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the maximum amount of RAM the worker may consume.
     *
     * @return int
     */
    public function memory(): int
    {
        return $this->memory;
    }
    
    /**
     * Returns the maximum number of seconds a worker may run.
     *
     * @return int
     */
    public function timeout(): int
    {
        return $this->timeout;
    }
    
    /**
     * Returns the number of seconds to wait in between polling the queue.
     *
     * @return positive-int
     */
    public function sleep(): int
    {
        return $this->sleep;
    }
    
    /**
     * Returns the maximum number of jobs to run.
     *
     * @return int
     */
    public function maxJobs(): int
    {
        return $this->maxJobs;
    }
    
    /**
     * Returns true if the worker should stop when the queue is empty, otherwise false.
     *
     * @return bool
     */
    public function stopWhenEmpty(): bool
    {
        return $this->stopWhenEmpty;
    }
}