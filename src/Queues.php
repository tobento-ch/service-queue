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

/**
 * Queues
 */
final class Queues implements QueuesInterface, QueueInterface
{
    /**
     * @var array<string, QueueInterface>
     */
    private array $queues = [];
    
    /**
     * Create a new Queues.
     *
     * @param QueueInterface ...$queues
     */
    public function __construct(
        QueueInterface ...$queues,
    ) {
        foreach($queues as $queue) {
            $this->queues[$queue->name()] = $queue;
        }
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
        return $this->queues[$name] ?? null;
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
        return 'queues';
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
        $queues = $this->queues;
        
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
        foreach($this->queues as $queue) {
            if (!is_null($job = $queue->getJob($id))) {
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
        
        foreach($this->queues as $queue) {
            foreach($queue->getAllJobs() as $key => $job) {
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
    
        foreach($this->queues as $queue) {
            $size += $queue->size();
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
        
        foreach($this->queues as $queue) {
            $cleared[] = $queue->clear();
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
        
        return is_null($firstKey) ? null : $this->queues[$firstKey];
    }
}