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
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\FailedJobHandlerFactory;
use Tobento\Service\Container\Container;

class InMemoryQueueTest extends TestCase
{
    public function testThatImplementsQueueInterface()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertInstanceof(QueueInterface::class, $queue);
    }
    
    public function testNameAndPriorityMethods()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            priority: 150,
        );
        
        $this->assertSame('primary', $queue->name());
        $this->assertSame(150, $queue->priority());
    }
    
    public function testPushMethod()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $job = new Mock\CallableJob(id: 'foo');
            
        $jobId = $queue->push($job);

        $this->assertSame('foo', $jobId);
    }
    
    public function testPushMethodPushingProcessIsCalled()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $param = new Mock\PushableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        
        $queue->push($job);
        
        $this->assertSame($job->getId(), $param->pushedJob()?->getId());
        $this->assertSame('primary', $param->pushedQueue()?->name());
    }
    
    public function testPopMethod()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $foo = new Mock\CallableJob();
        $bar = new Mock\CallableJob();
        $queue->push($foo);
        $queue->push($bar);
        
        $this->assertSame($foo, $queue->pop());
        $this->assertSame($bar, $queue->pop());
        $this->assertSame(null, $queue->pop());
    }
    
    public function testPopMethodWithPrioritizedJobs()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $foo = (new Mock\CallableJob())->priority(100);
        $bar = (new Mock\CallableJob())->priority(200);
        $queue->push($foo);
        $queue->push($bar);
        
        $this->assertSame($bar, $queue->pop());
        $this->assertSame($foo, $queue->pop());
        $this->assertSame(null, $queue->pop());
    }
    
    public function testPopMethodPoppingProcessIsCalled()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $param = new Mock\PoppableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        
        $queue->push($job);
        $poppedJob = $queue->pop();
        
        $this->assertSame($poppedJob->getId(), $param->poppedJob()?->getId());
        $this->assertSame('primary', $param->poppedQueue()?->name());
    }
    
    public function testGetJobMethod()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame($foo, $queue->getJob(id: 'foo'));
        $this->assertSame(null, $queue->getJob(id: 'bar'));
    }
    
    public function testGetAllJobsMethod()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertSame([], $queue->getAllJobs());
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame(['foo' => $foo], $queue->getAllJobs());
    }
    
    public function testSizeMethod()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertSame(0, $queue->size());
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame(1, $queue->size());
    }
    
    public function testClearMethod()
    {
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame(1, $queue->size());
        $this->assertTrue($queue->clear());
        $this->assertSame(0, $queue->size());
    }
}