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
use JsonSerializable;
use Throwable;

/**
 * Delay
 */
class Delay extends Parameter implements JsonSerializable, Failable
{
    /**
     * Create a new Delay.
     *
     * @param int $seconds The seconds to delay the jobs.
     */
    public function __construct(
        protected int $seconds,
    ) {}
    
    /**
     * Returns the seconds.
     *
     * @return int
     */
    public function seconds(): int
    {
        return $this->seconds;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['seconds' => $this->seconds()];
    }
    
    /**
     * Returns the failed job handler.
     *
     * @return callable
     */
    public function getFailedJobHandler(): callable
    {
        return [$this, 'processFailedJob'];
    }
    
    /**
     * Process failed job.
     *
     * @param JobInterface $job
     * @param Throwable $e
     * @return void
     */
    public function processFailedJob(JobInterface $job, Throwable $e): void
    {
        // Remove delay on failing, as the job might get repushed when failing:
        $job->parameters()->remove(Delay::class);
    }
}