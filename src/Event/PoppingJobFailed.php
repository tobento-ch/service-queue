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

use Tobento\Service\Queue\JobInterface;
use Throwable;

/**
 * PoppingJobFailed
 */
final class PoppingJobFailed
{
    /**
     * Create a new PoppingJobFailed.
     *
     * @param Throwable $exception
     * @param null|string $queue
     */
    public function __construct(
        private Throwable $exception,
        private null|string $queue,
    ) {}
    
    /**
     * Returns the exception.
     *
     * @return Throwable
     */
    public function exception(): Throwable
    {
        return $this->exception;
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
}