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

namespace Tobento\Service\Queue\Event;

use Tobento\Service\Queue\WorkerOptions;

/**
 * WorkerStarting
 */
final class WorkerStarting
{
    /**
     * Create a new WorkerStarting.
     *
     * @param null|string $queue The queue name.
     * @return WorkerOptions $options
     */
    public function __construct(
        private null|string $queue,
        private WorkerOptions $options,
    ) {}
    
    /**
     * Returns the queue.
     *
     * @return null|string
     */
    public function queue(): null|string
    {
        return $this->queue;
    }
    
    /**
     * Returns the options.
     *
     * @return WorkerOptions
     */
    public function options(): WorkerOptions
    {
        return $this->options;
    }
}