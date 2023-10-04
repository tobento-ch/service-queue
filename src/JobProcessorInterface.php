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
 * JobProcessorInterface
 */
interface JobProcessorInterface
{
    /**
     * Add a job handler for the specified job.
     *
     * @param string $job
     * @param string|JobHandlerInterface $handler
     * @return static $this
     */
    public function addJobHandler(string $job, string|JobHandlerInterface $handler): static;
    
    /**
     * Process job.
     *
     * @param JobInterface $job
     * @return void
     * @throws Throwable
     */
    public function processJob(JobInterface $job): void;
    
    /**
     * Before process job.
     *
     * @param JobInterface $job
     * @return null|JobInterface Null if job cannot be processed.
     * @throws Throwable
     */
    public function beforeProcessJob(JobInterface $job): null|JobInterface;
    
    /**
     * After process job.
     *
     * @param JobInterface $job
     * @return JobInterface
     * @throws Throwable
     */
    public function afterProcessJob(JobInterface $job): JobInterface;
    
    /**
     * Process pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @return JobInterface
     * @throws Throwable
     */
    public function processPushingJob(JobInterface $job, QueueInterface $queue): JobInterface;
    
    /**
     * Process popping job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @return JobInterface
     * @throws Throwable
     */
    public function processPoppingJob(JobInterface $job, QueueInterface $queue): JobInterface;
    
    /**
     * Process failed job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     * @throws Throwable
     */
    public function processFailedJob(JobInterface $job, null|Throwable $e): void;
}