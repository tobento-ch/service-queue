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
use Tobento\Service\Queue\LazyQueues;
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
use Psr\Container\ContainerInterface;

class LazyQueuesTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $queues = new LazyQueues(new Container(), []);
        
        $this->assertInstanceof(QueuesInterface::class, $queues);
        $this->assertInstanceof(QueueInterface::class, $queues);
    }
    
    public function testUsingFactory()
    {
        $container = new Container();
        $container->set(JobProcessorInterface::class, JobProcessor::class);
        
        $queues = new LazyQueues(container: $container, queues: [
            'primary' => [
                'factory' => QueueFactory::class,
                'config' => [
                    'queue' => SyncQueue::class,
                    'priority' => 100,
                ],
            ],
        ]);
        
        $this->assertSame('primary', $queues->get('primary')?->name());
        $this->assertSame(100, $queues->get('primary')?->priority());
        $this->assertTrue($queues->has('primary'));
    }
    
    public function testUsingFactoryThrowsQueueExceptionOnFailure()
    {
        $this->expectException(QueueException::class);
        
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => [
                'factory' => QueueFactory::class,
                'config' => [
                    'queue' => SyncQueue::class,
                    'priority' => 100,
                ],
            ],
        ]);
        
        $queues->get('primary');
    }
    
    public function testUsingClosure()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => static function (string $name, ContainerInterface $c): QueueInterface {
                return new NullQueue($name);
            },
        ]);
        
        $this->assertSame('primary', $queues->get('primary')?->name());
        $this->assertTrue($queues->has('primary'));
    }
    
    public function testUsingClosureThrowsQueueExceptionOnFailure()
    {
        $this->expectException(QueueException::class);
        
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => static function (string $name, ContainerInterface $c): QueueInterface {
                throw new \Exception('error');
            },
        ]);
        
        $queues->get('primary');
    }
    
    public function testUsingQueue()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new NullQueue('primary'),
        ]);
        
        $this->assertSame('primary', $queues->get('primary')?->name());
        $this->assertTrue($queues->has('primary'));
    }
    
    public function testHasAndGetMethod()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new NullQueue('primary'),
        ]);
        
        $this->assertTrue($queues->has('primary'));
        $this->assertFalse($queues->has('secondary'));
        
        $this->assertSame('primary', $queues->get('primary')?->name());
        $this->assertSame(null, $queues->get('secondary')?->name());
    }
    
    public function testNamesMethod()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new NullQueue('primary'),
            'secondary' => new NullQueue('secondary'),
        ]);
        
        $this->assertSame(['primary', 'secondary'], $queues->names());
    }
    
    public function testQueueMethod()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new NullQueue('primary'),
        ]);
        
        $this->assertSame('primary', $queues->queue('primary')->name());
    }
    
    public function testQueueMethodThrowsQueueExceptionIfNotFound()
    {
        $this->expectException(QueueException::class);
        
        $queues = new LazyQueues(container: new Container(), queues: []);
        
        $queues->queue('primary');
    }
    
    public function testNameAndPriorityMethods()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new NullQueue('primary'),
        ]);
        
        $this->assertSame('lazyQueues', $queues->name());
        $this->assertSame(100, $queues->priority());
    }
    
    public function testPushMethodUsesFirstQueueIfNoneSpecified()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new NullQueue('primary'),
            'secondary' => new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        ]);
        
        $job = new Mock\CallableJob(id: 'foo');
            
        $jobId = $queues->push($job);
        
        $this->assertSame('foo', $jobId);
        $this->assertSame('primary', $job->parameters()->get(Parameter\Queue::class)?->name());
    }
    
    public function testPushMethodUsesSpecifiedQueue()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new NullQueue('primary'),
            'secondary' => new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        ]);
        
        $job = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
            
        $jobId = $queues->push($job);
        
        $this->assertSame('foo', $jobId);
        $this->assertSame('secondary', $job->parameters()->get(Parameter\Queue::class)?->name());
    }
    
    public function testPushMethodThrowsQueueExceptionIfNoQueues()
    {
        $this->expectException(QueueException::class);
        
        $queues = new LazyQueues(container: new Container(), queues: []);
        
        $job = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
            
        $queues->push($job);
    }
    
    public function testPopMethod()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'primary' => new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        ]);
        
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
        $queues = new LazyQueues(container: new Container(), queues: [
            'secondary' => new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
                priority: 100,
            ),
            'primary' => new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
                priority: 200,
            ),
        ]);
        
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
        $queues = new LazyQueues(container: new Container(), queues: [
            'secondary' => new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            'primary' => new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        ]);
        
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
        $queues = new LazyQueues(container: new Container(), queues: [
            'secondary' => new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            'primary' => new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        ]);
        
        $foo = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
        $bar = (new Mock\CallableJob(id: 'bar'))->queue('primary');
        $queues->push($foo);
        $queues->push($bar);
        
        $this->assertSame($foo, $queues->getAllJobs()['foo'] ?? null);
        $this->assertSame($bar, $queues->getAllJobs()['bar'] ?? null);
    }
    
    public function testSizeMethod()
    {
        $queues = new LazyQueues(container: new Container(), queues: [
            'secondary' => new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            'primary' => new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        ]);
        
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
        $queues = new LazyQueues(container: new Container(), queues: [
            'secondary' => new InMemoryQueue(
                name: 'secondary',
                jobProcessor: new JobProcessor(new Container()),
            ),
            'primary' => new InMemoryQueue(
                name: 'primary',
                jobProcessor: new JobProcessor(new Container()),
            ),
        ]);
        
        $foo = (new Mock\CallableJob(id: 'foo'))->queue('secondary');
        $bar = (new Mock\CallableJob(id: 'bar'))->queue('primary');
        $queues->push($foo);
        $queues->push($bar);
        
        $this->assertSame(2, $queues->size());
        $this->assertTrue($queues->clear());
        $this->assertSame(0, $queues->size());
    }
}