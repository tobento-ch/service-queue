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
use Tobento\Service\Queue\Parameter\Poppable;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\QueueInterface;
use JsonSerializable;
use Closure;

class PoppableParameter extends Parameter implements Poppable, JsonSerializable
{
    private null|JobInterface $poppedJob = null;
    private null|QueueInterface $poppedQueue = null;
    
    public function __construct(
        protected null|string $name = null,
        protected null|Closure $handler = null,
        protected int $priority = 0,
        protected bool $returnNull = false,
    ) {}
    
    public function poppedJob(): null|JobInterface
    {
        return $this->poppedJob;
    }
    
    public function poppedQueue(): null|QueueInterface
    {
        return $this->poppedQueue;
    }
    
    public function getName(): string
    {
        return $this->name ?: parent::getName();
    }
    
    public function getPriority(): int
    {
        return $this->priority;
    }

    public function jsonSerialize(): array
    {
        return ['name' => $this->name];
    }
    
    /**
     * Returns the popping job handler.
     *
     * @return callable
     */
    public function getPoppingJobHandler(): callable
    {
        if (!is_null($this->handler)) {
            return $this->handler;
        }
        
        return [$this, 'poppingJob'];
    }
    
    /**
     * Pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param ... any parameters resolvable by your container.
     * @return null|JobInterface
     */
    public function poppingJob(JobInterface $job, QueueInterface $queue): null|JobInterface
    {
        $this->poppedJob = $job;
        $this->poppedQueue = $queue;
        return $this->returnNull ? null : $job;
    }
}