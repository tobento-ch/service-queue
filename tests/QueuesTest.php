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
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\QueueFactory;
use Tobento\Service\Queue\QueueException;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Container\Container;

class QueuesTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $queues = new Queues();
        
        $this->assertInstanceof(QueuesInterface::class, $queues);
        $this->assertInstanceof(QueueInterface::class, $queues);
    }
    
    public function testHasAndGetMethod()
    {
        $queues = new Queues(
            new NullQueue('primary'),
        );
        
        $this->assertTrue($queues->has('primary'));
        $this->assertFalse($queues->has('secondary'));
        
        $this->assertSame('primary', $queues->get('primary')?->name());
        $this->assertSame(null, $queues->get('secondary')?->name());
    }
    
    public function testNamesMethod()
    {
        $queues = new Queues(
            new NullQueue('primary'),
            new NullQueue('secondary'),
        );
        
        $this->assertSame(['primary', 'secondary'], $queues->names());
    }
    
    public function testQueueMethod()
    {
        $queues = new Queues(
            new NullQueue('primary'),
        );
        
        $this->assertSame('primary', $queues->queue('primary')->name());
    }
    
    public function testQueueMethodThrowsQueueExceptionIfNotFound()
    {
        $this->expectException(QueueException::class);
        
        $queues = new Queues();
        
        $queues->queue('primary');
    }
    
    public function testNameAndPriorityMethods()
    {
        $queues = new Queues(
            new NullQueue('primary'),
        );
        
        $this->assertSame('queues', $queues->name());
        $this->assertSame(100, $queues->priority());
    }
    
    public function testPushMethodUsesFirstQueueIfNoneSpecified()
    {
        $queues = new Queues(
            new NullQueue('primary'),
            new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        );
        
        $job = new Mock\CallableJob(id: 'foo');
            
        $jobId = $queues->push($job);
        
        $this->assertSame('foo', $jobId);
        $this->assertSame('primary', $job->parameters()->get(Parameter\Queue::class)?->name());
    }
    
    public function testPushMethodUsesSpecifiedQueue()
    {
        $queues = new Queues(
            new NullQueue('primary'),
            new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        );
        
        $job = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
            
        $jobId = $queues->push($job);
        
        $this->assertSame('foo', $jobId);
        $this->assertSame('secondary', $job->parameters()->get(Parameter\Queue::class)?->name());
    }
    
    public function testPushMethodThrowsQueueExceptionIfNoQueues()
    {
        $this->expectException(QueueException::class);
        
        $queues = new Queues();
        
        $job = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
            
        $queues->push($job);
    }
    
    public function testPopMethod()
    {
        $queues = new Queues(
            new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        );
        
        $foo = new Mock\CallableJob(id: 'foo');
        $bar = new Mock\CallableJob(id: 'bar');
        $queues->push($foo);
        $queues->push($bar);
        
        $this->assertSame($foo, $queues->pop());
        $this->assertSame($bar, $queues->pop());
        $this->assertSame(null, $queues->pop());
    }
    
    public function testPopMethodWithPrioritizedQueuesUsesHighestFirst()
    {
        $queues = new Queues(
            new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
                priority: 100,
            ),
            new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
                priority: 200,
            ),
        );
        
        $foo = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
        $bar = (new Mock\CallableJob(id: 'bar'))->queue('primary');
        $queues->push($foo);
        $queues->push($bar);
        
        $this->assertSame($bar, $queues->pop());
        $this->assertSame($foo, $queues->pop());
        $this->assertSame(null, $queues->pop());
    }
    
    public function testGetJobMethod()
    {
        $queues = new Queues(
            new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        );
        
        $foo = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
        $bar = (new Mock\CallableJob(id: 'bar'))->queue('primary');
        $queues->push($foo);
        $queues->push($bar);
        
        $this->assertSame($foo, $queues->getJob('foo'));
        $this->assertSame($bar, $queues->getJob('bar'));
        $this->assertSame(null, $queues->getJob('baz'));
    }
    
    public function testGetAllJobsMethod()
    {
        $queues = new Queues(
            new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        );
        
        $foo = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
        $bar = (new Mock\CallableJob(id: 'bar'))->queue('primary');
        $queues->push($foo);
        $queues->push($bar);
        
        $this->assertSame($foo, $queues->getAllJobs()['foo'] ?? null);
        $this->assertSame($bar, $queues->getAllJobs()['bar'] ?? null);
    }
    
    public function testSizeMethod()
    {
        $queues = new Queues(
            new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        );
        
        $foo = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
        $bar = (new Mock\CallableJob(id: 'bar'))->queue('primary');
        $baz = (new Mock\CallableJob(id: 'baz'))->queue('primary');
        $queues->push($foo);
        $queues->push($bar);
        $queues->push($baz);
        
        $this->assertSame(3, $queues->size());
    }
    
    public function testClearMethod()
    {
        $queues = new Queues(
            new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        );
        
        $foo = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
        $bar = (new Mock\CallableJob(id: 'bar'))->queue('primary');
        $queues->push($foo);
        $queues->push($bar);
        
        $this->assertSame(2, $queues->size());
        $this->assertTrue($queues->clear());
        $this->assertSame(0, $queues->size());
    }
}