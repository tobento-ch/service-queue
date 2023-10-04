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

namespace Tobento\Service\Queue\Test\Storage;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Queue\Test\Mock;
use Tobento\Service\Queue\Storage\QueueFactory;
use Tobento\Service\Queue\QueueFactoryInterface;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\QueueException;
use Tobento\Service\Storage\JsonFileStorage;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Storage\PdoMySqlStorage;
use Tobento\Service\Storage\PdoMariaDbStorage;
use Tobento\Service\Storage\StorageException;
use Tobento\Service\Container\Container;
use Tobento\Service\Clock\FrozenClock;
use Tobento\Service\Database\Databases;
use Tobento\Service\Database\PdoDatabase;
use PDO;

class QueueFactoryTest extends TestCase
{
    public function testThatImplementsQueueFactoryInterface()
    {
        $factory = new QueueFactory(
            jobProcessor: new JobProcessor(new Container()),
            clock: new FrozenClock(),
        );
        
        $this->assertInstanceof(QueueFactoryInterface::class, $factory);
    }
    
    public function testCreateQueueMethodWithInMemoryStorage()
    {
        $factory = new QueueFactory(
            jobProcessor: new JobProcessor(new Container()),
            clock: new FrozenClock(),
        );
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'table' => 'jobs',
            'storage' => InMemoryStorage::class,
            'priority' => 200,
        ]);
        
        $this->assertInstanceof(InMemoryStorage::class, $queue->storage());
        $this->assertSame('primary', $queue->name());
        $this->assertSame(200, $queue->priority());
    }
    
    public function testCreateQueueMethodWithJsonFileStorage()
    {
        $factory = new QueueFactory(
            jobProcessor: new JobProcessor(new Container()),
            clock: new FrozenClock(),
        );
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'table' => 'jobs',
            'storage' => JsonFileStorage::class,
            'dir' => __DIR__,
            'priority' => 200,
        ]);
        
        $this->assertInstanceof(JsonFileStorage::class, $queue->storage());
        $this->assertSame('primary', $queue->name());
        $this->assertSame(200, $queue->priority());
    }
    
    public function testCreateQueueMethodWithPdoMariaDbStorage()
    {
        // For simplicity, we use sqlite, but this throws error!
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('PdoMariaDbStorage only supports mysql driver');
        
        $factory = new QueueFactory(
            jobProcessor: new JobProcessor(new Container()),
            clock: new FrozenClock(),
            databases: new Databases(
                new PdoDatabase(
                    pdo: new PDO('sqlite::memory:'),
                    name: 'sqlite',
                ),
            ),
        );
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'table' => 'jobs',
            'storage' => PdoMariaDbStorage::class,
            'database' => 'sqlite',
            'priority' => 200,
        ]);
    }
    
    public function testCreateQueueMethodWithPdoMySqlStorage()
    {
        // For simplicity, we use sqlite, but this throws error!
        $this->expectException(StorageException::class);
        $this->expectExceptionMessage('PdoMySqlStorage only supports mysql driver');
        
        $factory = new QueueFactory(
            jobProcessor: new JobProcessor(new Container()),
            clock: new FrozenClock(),
            databases: new Databases(
                new PdoDatabase(
                    pdo: new PDO('sqlite::memory:'),
                    name: 'sqlite',
                ),
            ),
        );
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'table' => 'jobs',
            'storage' => PdoMySqlStorage::class,
            'database' => 'sqlite',
            'priority' => 200,
        ]);
    }    
    
    public function testCreateQueueMethodThrowsQueueExceptionIfMissingConfig()
    {
        $this->expectException(QueueException::class);
        
        $factory = new QueueFactory(
            jobProcessor: new JobProcessor(new Container()),
            clock: new FrozenClock(),
        );
        
        $factory->createQueue(name: 'primary', config: []);
    }
    
    public function testCreateQueueMethodThrowsQueueExceptionIfMissingTable()
    {
        $this->expectException(QueueException::class);
        
        $factory = new QueueFactory(
            jobProcessor: new JobProcessor(new Container()),
            clock: new FrozenClock(),
        );
        
        $factory->createQueue(name: 'primary', config: [
            'storage' => InMemoryStorage::class,
        ]);
    }
}