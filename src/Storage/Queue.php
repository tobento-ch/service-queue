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

namespace Tobento\Service\Queue\Storage;

use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\ParametersFactoryInterface;
use Tobento\Service\Queue\ParametersFactory;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\ItemInterface;
use Psr\Clock\ClockInterface;

/**
 * Queue using storage
 */
final class Queue implements QueueInterface
{
    /**
     * @var ParametersFactoryInterface
     */
    protected ParametersFactoryInterface $parametersFactory;
    
    /**
     * Create a new Queue.
     *
     * @param string $name
     * @param JobProcessorInterface $jobProcessor
     * @param StorageInterface $storage
     * @param ClockInterface $clock
     * @param string $table
     * @param int $priority
     * @param null|ParametersFactoryInterface $parametersFactory
     */
    public function __construct(
        private string $name,
        private JobProcessorInterface $jobProcessor,
        private StorageInterface $storage,
        private ClockInterface $clock,
        private string $table,
        private int $priority = 100,
        null|ParametersFactoryInterface $parametersFactory = null,
    ) {
        $this->parametersFactory = $parametersFactory ?: new ParametersFactory();
        
        $storage->tables()->add(
            table: $table,
            columns: ['id', 'queue', 'job_id', 'name', 'payload', 'parameters', 'priority', 'available_at'],
            primaryKey: 'id',
        );
    }
    
    /**
     * Returns the queue name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Returns the queue priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority;
    }
    
    /**
     * Push a new job onto the queue.
     *
     * @param JobInterface $job
     * @return string The job id
     */
    public function push(JobInterface $job): string
    {
        $job = $this->jobProcessor->processPushingJob($job, $this);
        
        // Get the priority if any defined:
        $priority = (int) $job->parameters()->get(Parameter\Priority::class)?->priority();
        
        // Handle delayed job:
        $availableAt = 0;
        $delayInSeconds = (int) $job->parameters()->get(Parameter\Delay::class)?->seconds();
        
        if ($delayInSeconds > 0) {
            $modified = $this->clock->now()->modify('+'.$delayInSeconds.' seconds');
            $availableAt = $modified ? $modified->getTimestamp() : 0;
        }
        
        $this->storage
            ->table($this->table)
            ->insert([
                'queue' => $this->name(),
                'job_id' => $job->getId(),
                'name' => $job->getName(),
                'payload' => $job->getPayload(),
                'parameters' => (string)$job->parameters(),
                'priority' => $priority,
                'available_at' => $availableAt,
            ]);
        
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
        $item = $this->storage
            ->table($this->table)
            ->where('queue', '=', $this->name())
            ->where('available_at', '<=', $this->clock->now()->getTimestamp())
            ->order('id', 'asc')
            ->order('priority', 'desc')
            ->first();
        
        if (is_null($item)) {
            return null;
        }
        
        $this->storage
            ->table($this->table)
            ->where('id', '=', $item->get('id'))
            ->delete();
        
        $job = $this->createJobFromItem($item);
        
        return $this->jobProcessor->processPoppingJob($job, $this);
    }
    
    /**
     * Returns the job or null if not found.
     *
     * @param string $id The job id.
     * @return null|JobInterface
     */
    public function getJob(string $id): null|JobInterface
    {
        $item = $this->storage
            ->table($this->table)
            ->where('job_id', '=', $id)
            ->first();
        
        return is_null($item) ? null : $this->createJobFromItem($item);
    }
    
    /**
     * Returns all jobs.
     *
     * @return iterable<int|string, JobInterface>
     */
    public function getAllJobs(): iterable
    {
        $items = $this->storage
            ->table($this->table)
            ->where('queue', '=', $this->name())
            ->get();
        
        return $items->map(function(array $item) {
            return $this->createJobFromItem($item);
        });
    }
    
    /**
     * Returns the number of jobs in queue.
     *
     * @return int
     */
    public function size(): int
    {
        return $this->storage
            ->table($this->table)
            ->where('queue', '=', $this->name())
            ->count();
    }
    
    /**
     * Deletes all jobs from the queue.
     *
     * @return bool True if the queue was successfully cleared. False if there was an error.
     */
    public function clear(): bool
    {
        $this->storage
            ->table($this->table)
            ->where('queue', '=', $this->name())
            ->delete();
        
        return true;
    }
    
    /**
     * Returns the storage.
     *
     * @return StorageInterface
     */
    public function storage(): StorageInterface
    {
        return $this->storage;
    }
    
    /**
     * Create job from item.
     *
     * @param array|ItemInterface $item
     * @return JobInterface
     */
    private function createJobFromItem(array|ItemInterface $item): JobInterface
    {
        if (is_array($item)) {
            return new Job(
                id: $item['job_id'],
                name: $item['name'],
                payload: json_decode($item['payload'], true),
                parameters: $this->parametersFactory->createFromJsonString($item['parameters']),
            );
        }
        
        return new Job(
            id: $item->get('job_id'),
            name: $item->get('name'),
            payload: json_decode($item->get('payload'), true),
            parameters: $this->parametersFactory->createFromJsonString($item->get('parameters')),
        );
    }
}