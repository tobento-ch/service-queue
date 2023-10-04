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
 * WorkerStopped
 */
final class WorkerStopped
{
    /**
     * Create a new WorkerStopped.
     *
     * @param int $status
     * @param null|string $queue The queue name.
     * @return WorkerOptions $options
     */
    public function __construct(
        private int $status,
        private null|string $queue,
        private WorkerOptions $options,
    ) {}
    
    /**
     * Returns the status.
     *
     * @return int
     */
    public function status(): int
    {
        return $this->status;
    }
    
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