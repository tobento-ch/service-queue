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
 * JobFailed
 */
final class JobFailed
{
    /**
     * Create a new JobFailed.
     *
     * @param JobInterface $job
     * @param Throwable $exception
     */
    public function __construct(
        private JobInterface $job,
        private Throwable $exception,
    ) {}
    
    /**
     * Returns the job.
     *
     * @return JobInterface
     */
    public function job(): JobInterface
    {
        return $this->job;
    }
    
    /**
     * Returns the exception.
     *
     * @return Throwable
     */
    public function exception(): Throwable
    {
        return $this->exception;
    }
}