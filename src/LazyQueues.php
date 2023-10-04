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
use Tobento\Service\Autowire\AutowireException;
use Throwable;

/**
 * LazyQueues
 */
final class LazyQueues implements QueuesInterface, QueueInterface
{
    /**
     * @var Autowire
     */
    protected Autowire $autowire;
    
    /**
     * @var array<string, QueueInterface>
     */
    protected array $createdQueues = [];
    
    /**
     * Create a new LazyQueues.
     *
     * @param ContainerInterface $container
     * @param array $queues
     */
    public function __construct(
        ContainerInterface $container,
        protected array $queues,
    ) {
        $this->autowire = new Autowire($container);
    }
    
    /**
     * Returns the queue.
     *
     * @param string $name
     * @return QueueInterface
     */
    public function queue(string $name): QueueInterface
    {
        if (!is_null($queue = $this->get($name))) {
            return $queue;
        }
        
        throw new QueueException(sprintf('Queue %s not found', $name));
    }
    
    /**
     * Returns the queue if exists, otherwise null.
     *
     * @param string $name
     * @return null|QueueInterface
     * @throws QueueException If queue does not exist
     */
    public function get(string $name): null|QueueInterface
    {
        if (isset($this->createdQueues[$name])) {
            return $this->createdQueues[$name];
        }
        
        if (!array_key_exists($name, $this->queues)) {
            return null;
        }

        if ($this->queues[$name] instanceof QueueInterface) {
            return $this->queues[$name];
        }
        
        // create queue from callable:
        if (is_callable($this->queues[$name])) {
            try {
                return $this->autowire->call($this->queues[$name], ['name' => $name]);
            } catch (Throwable $e) {
                throw new QueueException($e->getMessage(), (int)$e->getCode(), $e);
            }
        }
        
        // create queue from factory:
        if (!isset($this->queues[$name]['factory'])) {
            return null;
        }
        
        try {
            $factory = $this->autowire->resolve($this->queues[$name]['factory']);
        } catch (AutowireException $e) {
            throw new QueueException($e->getMessage(), (int)$e->getCode(), $e);
        }
        
        if (! $factory instanceof QueueFactoryInterface) {
            return null;
        }
        
        $config = $this->queues[$name]['config'] ?? [];
        
        return $this->createdQueues[$name] = $factory->createQueue($name, $config);
    }
    
    /**
     * Returns true if queue exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->queues);
    }
    
    /**
     * Returns all queue names.
     *
     * @return array
     */
    public function names(): array
    {
        return array_keys($this->queues);
    }
    
    /**
     * Returns the queue name.
     *
     * @return string
     */
    public function name(): string
    {
        return 'lazyQueues';
    }
    
    /**
     * Returns the queue priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return 100;
    }
    
    /**
     * Push a new job onto the queue.
     *
     * @param JobInterface $job
     * @return string The job id
     */
    public function push(JobInterface $job): string
    {
        $queue = $job->parameters()->get(Parameter\Queue::class);
        
        if ($queue instanceof Parameter\Queue) {
            $queue = $this->queue($queue->name());
        } else {
            $queue = $this->getFirstQueue();
        }
        
        if (is_null($queue)) {
            throw new QueueException('No queue found to push the job');
        }
        
        $job->parameter(new Parameter\Queue(name: $queue->name()));
        
        $queue->push($job);
        
        return $job->getId();
    }
    
    /**
     * Pop the next job off of the queue.
     *
     * @return null|JobInterface
     * @throws \Throwable
     */
    public function pop(): null|JobInterface
    {
        $queues = [];
        
        foreach(array_keys($this->queues) as $name) {
            $queues[] = $this->queue($name);
        }
        
        usort(
            $queues,
            fn (QueueInterface $a, QueueInterface $b): int
                => $b->priority() <=> $a->priority()
        );
        
        foreach($queues as $queue) {
            if (!is_null($job = $queue->pop())) {
                return $job;
            }
        }
        
        return null;
    }
    
    /**
     * Returns the job or null if not found.
     *
     * @param string $id The job id.
     * @return null|JobInterface
     */
    public function getJob(string $id): null|JobInterface
    {
        foreach(array_keys($this->queues) as $name) {
            if (!is_null($job = $this->queue($name)->getJob($id))) {
                return $job;
            }
        }
        
        return null;
    }
    
    /**
     * Returns all jobs.
     *
     * @return iterable<int|string, JobInterface>
     */
    public function getAllJobs(): iterable
    {
        $jobs = [];
        
        foreach(array_keys($this->queues) as $name) {
            foreach($this->queue($name)->getAllJobs() as $key => $job) {
                $jobs[$key] = $job;
            }
        }
        
        return $jobs;
    }
    
    /**
     * Returns the number of jobs in queue.
     *
     * @return int
     */
    public function size(): int
    {
        $size = 0;
    
        foreach(array_keys($this->queues) as $name) {
            $size += $this->queue($name)->size();
        }
        
        return $size;
    }
    
    /**
     * Deletes all jobs from the queue.
     *
     * @return bool True if the queue was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        $cleared = [];
        
        foreach(array_keys($this->queues) as $name) {
            $cleared[] = $this->queue($name)->clear();
        }
        
        return !in_array(false, $cleared, true);
    }
    
    /**
     * Returns the first queue or null if none.
     *
     * @return null|QueueInterface
     */
    private function getFirstQueue(): null|QueueInterface
    {
        $firstKey = array_key_first($this->queues);
        
        return is_null($firstKey) ? null : $this->queue($firstKey);
    }
}