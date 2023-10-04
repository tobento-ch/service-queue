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

use Throwable;

/**
 * FailedJobHandlerInterface
 */
interface FailedJobHandlerInterface
{
    /**
     * Handle the failed job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     */
    public function handleFailedJob(JobInterface $job, null|Throwable $e = null): void;
}