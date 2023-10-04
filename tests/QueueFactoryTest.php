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

namespace Tobento\Service\Queue\Test;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Queue\Test\Mock;
use Tobento\Service\Queue\QueueFactory;
use Tobento\Service\Queue\QueueFactoryInterface;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\QueueException;
use Tobento\Service\Container\Container;

class QueueFactoryTest extends TestCase
{
    public function testThatImplementsQueueFactoryInterface()
    {
        $this->assertInstanceof(
            QueueFactoryInterface::class,
            new QueueFactory(new JobProcessor(new Container()))
        );
    }
    
    public function testCreateQueueMethodWithInMemoryQueue()
    {
        $factory = new QueueFactory(new JobProcessor(new Container()));
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'queue' => InMemoryQueue::class,
            'priority' => 200,
        ]);
        
        $this->assertInstanceof(InMemoryQueue::class, $queue);
        $this->assertSame('primary', $queue->name());
        $this->assertSame(200, $queue->priority());
    }
    
    public function testCreateQueueMethodWithNullQueue()
    {
        $factory = new QueueFactory(new JobProcessor(new Container()));
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'queue' => NullQueue::class,
            'priority' => 200,
        ]);
        
        $this->assertInstanceof(NullQueue::class, $queue);
        $this->assertSame('primary', $queue->name());
        $this->assertSame(200, $queue->priority());
    }
    
    public function testCreateQueueMethodWithSyncQueue()
    {
        $factory = new QueueFactory(new JobProcessor(new Container()));
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'queue' => SyncQueue::class,
            'priority' => 200,
        ]);
        
        $this->assertInstanceof(SyncQueue::class, $queue);
        $this->assertSame('primary', $queue->name());
        $this->assertSame(200, $queue->priority());
    }
    
    public function testCreateQueueMethodThrowsQueueExceptionIfMissingQueue()
    {
        $this->expectException(QueueException::class);
        
        $factory = new QueueFactory(new JobProcessor(new Container()));
        
        $queue = $factory->createQueue(name: 'primary', config: []);
    }
    
    public function testCreateQueueMethodThrowsQueueExceptionIfInvalidQueue()
    {
        $this->expectException(QueueException::class);
        
        $factory = new QueueFactory(new JobProcessor(new Container()));
        
        $queue = $factory->createQueue(name: 'primary', config: [
            'queue' => UnknownQueue::class,
            'priority' => 200,
        ]);
    }
}