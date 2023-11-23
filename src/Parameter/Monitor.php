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

/**
 * Monitors the job process.
 */
class Monitor extends Parameter implements Processable
{
    /**
     * @var int|float
     */
    protected int|float $startTime = 0;
    
    /**
     * @var int|float
     */
    protected int|float $runtimeInSeconds = 0;

    /**
     * @var int|float
     */
    protected int|float $startMemory = 0;
    
    /**
     * @var int|float
     */
    protected int|float $memoryUsage = 0;
    
    /**
     * Returns the priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 100000;
    }
    
    /**
     * Returns the runtime in seconds.
     *
     * @return int|float
     */
    public function runtimeInSeconds(): int|float
    {
        return $this->runtimeInSeconds;
    }
    
    /**
     * Returns the memory usage in bytes.
     *
     * @return int|float
     */
    public function memoryUsage(): int|float
    {
        return $this->memoryUsage;
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
        return [$this, 'afterProcessJob'];
    }
    
    /**
     * Before process job handler.
     *
     * @param JobInterface $job
     * @return JobInterface
     */
    public function beforeProcessJob(JobInterface $job): JobInterface
    {
        $this->startTime = hrtime(true);
        $this->startMemory = memory_get_usage(true);
        
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
        $this->runtimeInSeconds = (hrtime(true) - $this->startTime) / 1e+6 / 1000;
        $this->memoryUsage = memory_get_usage(true) - $this->startMemory;
        
        return $job;
    }
}