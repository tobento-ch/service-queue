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

use Psr\Container\ContainerInterface;
use Tobento\Service\Autowire\Autowire;
use Throwable;

/**
 * JobProcessor
 */
class JobProcessor implements JobProcessorInterface
{
    /**
     * @var array<string, string|JobHandlerInterface>
     */
    protected array $jobHandlers = [];
    
    /**
     * Create a new JobHandler.
     *
     * @param ContainerInterface $container
     */
    public function __construct(
        protected ContainerInterface $container,
    ) {}

    /**
     * Add a job handler for the specified job.
     *
     * @param string $job
     * @param string|JobHandlerInterface $handler
     * @return static $this
     */
    public function addJobHandler(string $job, string|JobHandlerInterface $handler): static
    {
        $this->jobHandlers[$job] = $handler;
        
        return $this;
    }
    
    /**
     * Process job.
     *
     * @param JobInterface $job
     * @return void
     * @throws Throwable
     */
    public function processJob(JobInterface $job): void
    {
        // First, check for specific handler:
        if (isset($this->jobHandlers[$job->getName()])) {
            $handler = $this->jobHandlers[$job->getName()];
            
            if (is_string($handler)) {
                $handler = (new Autowire($this->container))->resolve($handler);
            }
            
            $handler->handleJob($job);
            return;
        }
        
        $autowire = new Autowire($this->container);
        
        // Secondly, check job has handler:
        if ($job instanceof CallableJobHandlerInterface) {
            $autowire->call($job->getCallableJobHandler(), ['job' => $job]);
            return;
        }
        
        if ($job instanceof JobHandlerInterface) {
            $job->handleJob($job);
            return;
        }
        
        // Finally, check job name is class and has handler:
        $class = $job->getName();
        
        if (!class_exists($class)) {
            throw new JobException(
                job: $job,
                message: sprintf('Unsupported job [%s]: Job name needs to be a class or specify a job handler', $class)
            );
        }
        
        $handler = $autowire->resolve($class);
        
        if ($handler instanceof CallableJobHandlerInterface) {
            $autowire->call($handler->getCallableJobHandler(), ['job' => $job]);
            return;
        }
        
        if ($handler instanceof JobHandlerInterface) {
            $handler->handleJob($job);
            return;
        }
        
        throw new JobException(
            job: $job,
            message: sprintf('Unable to handle job [%s] as unsupported handler', $class)
        );
    }
    
    /**
     * Before process job.
     *
     * @param JobInterface $job
     * @return null|JobInterface Null if job cannot be processed.
     * @throws Throwable
     */
    public function beforeProcessJob(JobInterface $job): null|JobInterface
    {
        $autowire = new Autowire($this->container);

        foreach($job->parameters() as $parameter) {
            if (
                $parameter instanceof Parameter\Processable
                && !is_null($handler = $parameter->getBeforeProcessJobHandler())
            ) {
                $job = $autowire->call($handler, ['job' => $job]);
                
                if (is_null($job)) {
                    return null;
                }
            }
        }

        return $job;
    }
    
    /**
     * After process job.
     *
     * @param JobInterface $job
     * @return JobInterface
     * @throws Throwable
     */
    public function afterProcessJob(JobInterface $job): JobInterface
    {
        $autowire = new Autowire($this->container);
        
        foreach($job->parameters() as $parameter) {
            if (
                $parameter instanceof Parameter\Processable
                && !is_null($handler = $parameter->getAfterProcessJobHandler())
            ) {
                $job = $autowire->call($handler, ['job' => $job]);
            }
        }

        return $job;
    }

    /**
     * Process pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @return JobInterface
     * @throws Throwable
     */
    public function processPushingJob(JobInterface $job, QueueInterface $queue): JobInterface
    {
        $job->parameter(new Parameter\Queue(name: $queue->name()));
        
        $autowire = new Autowire($this->container);

        // sorts by priority, highest first.
        foreach($job->parameters()->sort() as $parameter) {
            if ($parameter instanceof Parameter\Pushable) {
                $job = $autowire->call($parameter->getPushingJobHandler(), ['job' => $job, 'queue' => $queue]);
            }
        }
        
        return $job;
    }
    
    /**
     * Process popping job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @return JobInterface
     * @throws Throwable
     */
    public function processPoppingJob(JobInterface $job, QueueInterface $queue): JobInterface
    {
        $autowire = new Autowire($this->container);
        
        // sorts by priority, highest last.
        $callback = fn(ParameterInterface $a, ParameterInterface $b): int
            => $a->getPriority() <=> $b->getPriority();

        foreach($job->parameters()->sort($callback) as $parameter) {
            if ($parameter instanceof Parameter\Poppable) {
                $job = $autowire->call($parameter->getPoppingJobHandler(), ['job' => $job, 'queue' => $queue]);
            }
        }
        
        return $job;
    }
    
    /**
     * Process failed job.
     *
     * @param JobInterface $job
     * @param null|Throwable $e
     * @return void
     * @throws Throwable
     */
    public function processFailedJob(JobInterface $job, null|Throwable $e): void
    {
        $autowire = new Autowire($this->container);
        
        foreach($job->parameters() as $parameter) {
            if ($parameter instanceof Parameter\Failable) {
                $autowire->call($parameter->getFailedJobHandler(), ['job' => $job, 'e' => $e]);
            }
        }
    }
}