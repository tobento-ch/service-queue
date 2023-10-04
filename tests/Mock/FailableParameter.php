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
use Tobento\Service\Queue\Parameter\Failable;
use Tobento\Service\Queue\JobInterface;
use Closure;
use Throwable;

class FailableParameter extends Parameter implements Failable
{
    private null|JobInterface $failedJob = null;
    private null|Throwable $failedException = null;
    
    public function __construct(
        protected null|Closure $handler = null,
    ) {}
    
    public function failedJob(): null|JobInterface
    {
        return $this->failedJob;
    }
    
    public function failedException(): null|Throwable
    {
        return $this->failedException;
    }
    
    /**
     * Returns the failed job handler.
     *
     * @return callable
     */
    public function getFailedJobHandler(): callable
    {
        if (!is_null($this->handler)) {
            return $this->handler;
        }
        
        return [$this, 'failingJob'];
    }

    /**
     * Pushing job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @param ... any parameters resolvable by your container.
     * @return void
     */
    public function failingJob(JobInterface $job, null|Throwable $e): void
    {
        $this->failedJob = $job;
        $this->failedException = $e;
    }
}