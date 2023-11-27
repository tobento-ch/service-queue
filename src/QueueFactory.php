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

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * QueueFactory
 */
class QueueFactory implements QueueFactoryInterface
{
    /**
     * Create a new QueueFactory.
     *
     * @param JobProcessorInterface $jobProcessor
     * @param null|EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        protected JobProcessorInterface $jobProcessor,
        protected null|EventDispatcherInterface $eventDispatcher = null,
    ) {}
    
    /**
     * Create a new queue based on the configuration.
     *
     * @param string $name
     * @param array $config
     * @return QueueInterface
     * @throws QueueException
     */
    public function createQueue(string $name, array $config): QueueInterface
    {
        if (!isset($config['queue'])) {
            throw new QueueException(sprintf('Missing "queue" config on queue %s', $name));
        }

        if ($config['queue'] instanceof QueueInterface) {
            return $config['queue'];
        }
        
        if ($config['queue'] === InMemoryQueue::class) {
            return new InMemoryQueue(
                name: $name,
                jobProcessor: $this->jobProcessor,
                priority: $config['priority'] ?? 100,
            );
        }
        
        if ($config['queue'] === NullQueue::class) {
            return new NullQueue(
                name: $name,
                priority: $config['priority'] ?? 100,
            );
        }
        
        if ($config['queue'] === SyncQueue::class) {
            return new SyncQueue(
                name: $name,
                jobProcessor: $this->jobProcessor,
                eventDispatcher: $this->eventDispatcher,
                priority: $config['priority'] ?? 100,
            );
        }
        
        throw new QueueException(sprintf('Unable to create queue %s', $name));
    }
}