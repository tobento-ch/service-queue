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

use Tobento\Service\Queue\QueueFactoryInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\QueueException;
use Tobento\Service\Storage\StorageInterface;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Storage\PdoMySqlStorage;
use Tobento\Service\Storage\PdoMariaDbStorage;
use Tobento\Service\Database\DatabasesInterface;
use Tobento\Service\Database\PdoDatabaseInterface;
use Psr\Clock\ClockInterface;

/**
 * QueueFactory
 */
class QueueFactory implements QueueFactoryInterface
{
    /**
     * Create a new QueueFactory.
     *
     * @param JobProcessorInterface $jobProcessor
     * @param ClockInterface $clock
     * @param null|DatabasesInterface $databases
     */
    public function __construct(
        protected JobProcessorInterface $jobProcessor,
        protected ClockInterface $clock,
        protected null|DatabasesInterface $databases = null,
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
        $storage = $this->createStorage($name, $config);
        
        if (!isset($config['table'])) {
            throw new QueueException(sprintf('Missing "queue" config on queue %s', $name));
        }
        
        return new Queue(
            name: $name,
            jobProcessor: $this->jobProcessor,
            storage: $storage,
            clock: $this->clock,
            table: $config['table'],
            priority: $config['priority'] ?? 100,
        );
    }
    
    /**
     * Create storage.
     *
     * @param string $name
     * @param array $config
     * @return StorageInterface
     * @throws QueueException
     */
    protected function createStorage(string $name, array $config): StorageInterface
    {
        if (!isset($config['storage'])) {
            throw new QueueException(sprintf('Missing "storage" config on queue %s', $name));
        }
        
        if ($config['storage'] instanceof StorageInterface) {
            return $config['storage'];
        }
        
        if ($config['storage'] === JsonFileStorage::class) {
            
            if (!isset($config['dir'])) {
                throw new QueueException(sprintf('Missing "dir" config on queue %s', $name));
            }
            
            return new JsonFileStorage(dir: $config['dir']);
        }
        
        if ($config['storage'] === InMemoryStorage::class) {
            return new InMemoryStorage(items: []);
        }
        
        if ($config['storage'] === PdoMySqlStorage::class) {
            if (!isset($config['database'])) {
                throw new QueueException(sprintf('Missing "database" config on queue %s', $name));
            }
            
            $database = $this->databases?->get($config['database']);
            
            if (!$database instanceof PdoDatabaseInterface) {
                throw new QueueException(
                    sprintf('Storage "database" config needs to be a PdoDatabase on queue %s!', $name)
                );
            }

            return new PdoMySqlStorage($database->pdo());
        }
        
        if ($config['storage'] === PdoMariaDbStorage::class) {
            if (!isset($config['database'])) {
                throw new QueueException(sprintf('Missing "database" config on queue %s', $name));
            }
            
            $database = $this->databases?->get($config['database']);
            
            if (!$database instanceof PdoDatabaseInterface) {
                throw new QueueException(
                    sprintf('Storage "database" config needs to be a PdoDatabase on queue %s!', $name)
                );
            }

            return new PdoMariaDbStorage($database->pdo());
        }
        
        throw new QueueException(sprintf('Unable to create storage based on config on queue %s', $name));
    }
}