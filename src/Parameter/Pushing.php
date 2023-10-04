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
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Autowire\Autowire;
use Psr\Container\ContainerInterface;
use Closure;

/**
 * Pushing: Handler is executed before the job gets pushed to the queue.
 */
class Pushing extends Parameter implements Pushable
{
    /**
     * Create a new Pushing.
     *
     * @param Closure $handler Executed before the job gets pushed to the queue.
     * @param int $priority Higher gets executed first
     */
    public function __construct(
        protected Closure $handler,
        protected int $priority = 0,
    ) {}
    
    /**
     * Returns the priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    /**
     * Returns the handler.
     *
     * @return Closure
     */
    public function handler(): Closure
    {
        return $this->handler;
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
     * Pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @return void
     */
    public function pushingJob(JobInterface $job, QueueInterface $queue, ContainerInterface $container): void
    {
        (new Autowire($container))->call($this->handler(), ['job' => $job]);
    }
}