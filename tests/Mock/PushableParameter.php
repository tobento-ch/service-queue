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

namespace Tobento\Service\Queue\Test\Mock;

use Tobento\Service\Queue\Parameter\Parameter;
use Tobento\Service\Queue\Parameter\Pushable;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\QueueInterface;
use Closure;

class PushableParameter extends Parameter implements Pushable
{
    private null|JobInterface $pushedJob = null;
    private null|QueueInterface $pushedQueue = null;
    
    public function __construct(
        protected null|string $name = null,
        protected null|Closure $handler = null,
        protected int $priority = 0,
    ) {}
    
    public function pushedJob(): null|JobInterface
    {
        return $this->pushedJob;
    }
    
    public function pushedQueue(): null|QueueInterface
    {
        return $this->pushedQueue;
    }
    
    public function getName(): string
    {
        return $this->name ?: parent::getName();
    }
    
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    /**
     * Returns the pushing job handler.
     *
     * @return callable
     */
    public function getPushingJobHandler(): callable
    {
        if (!is_null($this->handler)) {
            return $this->handler;
        }
        
        return [$this, 'pushingJob'];
    }
    
    /**
     * Pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param ... any parameters resolvable by your container.
     * @return JobInterface
     */
    public function pushingJob(JobInterface $job, QueueInterface $queue): JobInterface
    {
        $this->pushedJob = $job;
        $this->pushedQueue = $queue;
        return $job;
    }
}