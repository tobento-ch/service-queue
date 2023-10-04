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
use Tobento\Service\Queue\Storage\Queue;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\FailedJobHandlerFactory;
use Tobento\Service\Queue\Event;
use Tobento\Service\Storage\InMemoryStorage;
use Tobento\Service\Container\Container;
use Tobento\Service\Clock\FrozenClock;

class InMemoryQueueTest extends TestCase
{
    public function testThatImplementsQueueInterface()
    {
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: new InMemoryStorage([]),
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $this->assertInstanceof(QueueInterface::class, $queue);
    }
    
    public function testNameAndPriorityMethods()
    {
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: new InMemoryStorage([]),
            clock: new FrozenClock(),
            table: 'jobs',
            priority: 150,
        );
        
        $this->assertSame('primary', $queue->name());
        $this->assertSame(150, $queue->priority());
    }
    
    public function testPushMethod()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
                
        $this->assertSame(0, $storage->table('jobs')->count());
        
        $jobId = $queue->push(new Mock\CallableJob(id: 'foo'));

        $this->assertSame('foo', $jobId);
        $this->assertSame(1, $storage->table('jobs')->count());
    }
    
    public function testPushMethodPushingProcessIsCalled()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $param = new Mock\PushableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        
        $queue->push($job);
        
        $this->assertSame($job->getId(), $param->pushedJob()?->getId());
        $this->assertSame('primary', $param->pushedQueue()?->name());
    }
    
    public function testPopMethod()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $foo = new Mock\CallableJob(id: 'foo');
        $bar = new Mock\CallableJob(id: 'bar');
        $queue->push($foo);
        $queue->push($bar);
        
        $this->assertSame('foo', $queue->pop()?->getId());
        $this->assertSame(1, $storage->table('jobs')->count());
        $this->assertSame('bar', $queue->pop()?->getId());
        $this->assertSame(null, $queue->pop()?->getId());
        $this->assertSame(0, $storage->table('jobs')->count());
    }
    
    public function testPopMethodWithPrioritizedJobs()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $foo = (new Mock\CallableJob(id: 'foo'))->priority(100);
        $bar = (new Mock\CallableJob(id: 'bar'))->priority(200);
        $queue->push($foo);
        $queue->push($bar);
        
        $this->assertSame('bar', $queue->pop()?->getId());
        $this->assertSame('foo', $queue->pop()?->getId());
        $this->assertSame(null, $queue->pop()?->getId());
    }
    
    public function testPopMethodWithDelayedJobs()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $foo = (new Mock\CallableJob(id: 'foo'))->delay(60);
        $bar = new Mock\CallableJob(id: 'bar');
        $baz = (new Mock\CallableJob(id: 'baz'))->delay(30);
        $queue->push($foo);
        $queue->push($bar);
        $queue->push($baz);
        
        $this->assertSame('bar', $queue->pop()?->getId());
        $this->assertSame(null, $queue->pop()?->getId());
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: (new FrozenClock())->modify('+31 seconds'),
            table: 'jobs',
        );
        
        $this->assertSame('baz', $queue->pop()?->getId());
        $this->assertSame(null, $queue->pop()?->getId());
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: (new FrozenClock())->modify('+60 seconds'),
            table: 'jobs',
        );
        
        $this->assertSame('foo', $queue->pop()?->getId());
        $this->assertSame(null, $queue->pop()?->getId());
    }
    
    public function testPopMethodPoppingProcessIsCalled()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $param = new Mock\PoppableParameter(name: 'pop');
        
        $job = (new Mock\CallableJob(id: 'foo'))->parameter($param);
        
        $queue->push($job);
        $poppedJob = $queue->pop();
        
        $this->assertSame($poppedJob->getId(), $poppedJob->parameters()->get('pop')?->poppedJob()?->getId());
        $this->assertSame('primary', $poppedJob->parameters()->get('pop')?->poppedQueue()?->name());
    }
    
    public function testGetJobMethod()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame('foo', $queue->getJob(id: 'foo')?->getId());
        $this->assertSame(null, $queue->getJob(id: 'bar'));
    }
    
    public function testGetAllJobsMethod()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $this->assertSame([], $queue->getAllJobs()->all());
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $job = $queue->getAllJobs()->first();
        $this->assertSame('foo', $job?->getId());
    }
    
    public function testSizeMethod()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $this->assertSame(0, $queue->size());
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame(1, $queue->size());
    }
    
    public function testClearMethod()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame(1, $queue->size());
        $this->assertTrue($queue->clear());
        $this->assertSame(0, $queue->size());
    }
    
    public function testStorageMethod()
    {
        $storage = new InMemoryStorage([]);
        
        $queue = new Queue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            storage: $storage,
            clock: new FrozenClock(),
            table: 'jobs',
        );
        
        $this->assertSame($storage, $queue->storage());
    }
}