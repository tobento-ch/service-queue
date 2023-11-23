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
use Tobento\Service\Queue\JobSkipException;
use JsonSerializable;

/**
 * The approximate duration the job needs to process.
 */
class Duration extends Parameter implements JsonSerializable, Processable
{
    /**
     * Create a new Duration.
     *
     * @param int $seconds
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
     * Returns the before process job handler.
     *
     * @return null|callable
     */
    public function getBeforeProcessJobHandler(): null|callable
    {
        return [$this, 'beforeProcessJob'];
    }
    
    /**
     * Returns the after process job handler.
     *
     * @return null|callable
     */
    public function getAfterProcessJobHandler(): null|callable
    {
        return null;
    }
    
    /**
     * Before process job handler.
     *
     * @param JobInterface $job
     * @return JobInterface
     * @throws \Throwable
     */
    public function beforeProcessJob(JobInterface $job): JobInterface
    {
        $secondsRemaining = $job->parameters()->get(SecondsBeforeTimingOut::class)?->seconds();
        
        if (is_null($secondsRemaining)) {
            return $job;
        }
        
        if ($this->seconds() > $secondsRemaining) {
            throw new JobSkipException(
                job: $job,
                message: 'Not enough seconds left to run the job',
                retry: true,
            );
        }
        
        return $job;
    }
}