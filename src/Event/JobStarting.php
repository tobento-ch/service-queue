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

/**
 * JobStarting
 */
final class JobStarting
{
    /**
     * Create a new JobStarting.
     *
     * @param JobInterface $job
     */
    public function __construct(
        private JobInterface $job,
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
}