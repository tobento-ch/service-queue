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
use Tobento\Service\Queue\Parameter\Processable;
use Tobento\Service\Queue\JobInterface;
use JsonSerializable;

class ProcessableParameter extends Parameter implements JsonSerializable, Processable
{
    private null|JobInterface $beforeProcessedJob = null;
    private null|JobInterface $afterProcessedJob = null;
    
    public function __construct(
        protected bool $unprocessableBeforeJob = false,
    ) {}
    
    public function beforeProcessedJob(): null|JobInterface
    {
        return $this->beforeProcessedJob;
    }
    
    public function afterProcessedJob(): null|JobInterface
    {
        return $this->afterProcessedJob;
    }
    
    /**
     * Returns the before process job handler.
     *
     * @return null|callable
     */
    public function getBeforeProcessJobHandler(): null|callable
    {
        return [$this, 'beforeProcessJob'];
        // or return null if not required
    }
    
    /**
     * Returns the after process job handler.
     *
     * @return null|callable
     */
    public function getAfterProcessJobHandler(): null|callable
    {
        return [$this, 'afterProcessJob'];
        // or return null if not required
    }
    
    /**
     * Before process job handler.
     *
     * @param JobInterface $job
     * @return null|JobInterface Null if job cannot be processed.
     */
    public function beforeProcessJob(JobInterface $job): null|JobInterface
    {
        $this->beforeProcessedJob = $job;
        
        if ($this->unprocessableBeforeJob) {
            return null;
        }
        
        return $job;
    }
    
    /**
     * After process job handler.
     *
     * @param JobInterface $job
     * @return JobInterface
     */
    public function afterProcessJob(JobInterface $job): JobInterface
    {
        $this->afterProcessedJob = $job;
        return $job;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [];
    }
}