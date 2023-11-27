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
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\Event;
use Tobento\Service\Container\Container;
use Tobento\Service\Event\Events;
use Tobento\Service\Collection\Collection;
use Throwable;

class SyncQueueTest extends TestCase
{
    public function testThatImplementsQueueInterface()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertInstanceof(QueueInterface::class, $queue);
    }
    
    public function testNameAndPriorityMethods()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            priority: 150,
        );
        
        $this->assertSame('primary', $queue->name());
        $this->assertSame(150, $queue->priority());
    }
    
    public function testJobIsProcessed()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $job = new Mock\CallableJob(payload: ['foo']);
            
        $queue->push($job);

        $this->assertSame(1, $job->processed());
        $this->assertSame(['foo'], $job->handledJob()->getPayload());
    }

    public function testJobIsProcessedWithPushingProcess()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $param = new Mock\PushableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        
        $queue->push($job);
        
        $this->assertSame($job->getId(), $param->pushedJob()?->getId());
        $this->assertSame('primary', $param->pushedQueue()?->name());
    }
    
    public function testJobIsProcessedWithBeforeAfterProcesses()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $param = new Mock\ProcessableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        
        $queue->push($job);
        
        $this->assertSame($job->getId(), $param->beforeProcessedJob()?->getId());
        $this->assertSame($job->getId(), $param->afterProcessedJob()?->getId());
    }
    
    public function testThrowsExceptionWhenJobFails()
    {
        $this->expectException(\Exception::class);
        
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $param = new Mock\ProcessableParameter();
        
        $job = (new Mock\CallableJob(failingJob: true))->parameter($param);
        
        $queue->push($job);
    }
    
    public function testPopMethod()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertSame(null, $queue->pop());
        $this->assertSame(null, $queue->pop());
    }
    
    public function testGetJobMethod()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertSame(null, $queue->getJob(id: 'id'));
    }
    
    public function testGetAllJobsMethod()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertSame([], $queue->getAllJobs());
    }
    
    public function testSizeMethod()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertSame(0, $queue->size());
    }
    
    public function testClearMethod()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $this->assertTrue($queue->clear());
    }

    public function testEvents()
    {
        $collection = new Collection();
        $events = new Events();
        
        $events->listen(function(Event\JobStarting $event) use ($collection) {
            $collection->add('starting:job', $event->job());
        });
        
        $events->listen(function(Event\JobFinished $event) use ($collection) {
            $collection->add('finished:job', $event->job());
        });
        
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            eventDispatcher: $events,
        );
        
        $job = new Mock\CallableJob();
                
        $queue->push($job);
        
        $this->assertTrue($job === $collection->get('starting:job'));
        $this->assertTrue($job === $collection->get('finished:job'));
    }
    
    public function testFailedEvent()
    {
        $collection = new Collection();
        $events = new Events();
        
        $events->listen(function(Event\JobFailed $event) use ($collection) {
            $collection->add('job', $event->job());
        });
        
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
            eventDispatcher: $events,
        );
        
        $job = new Mock\CallableJob(failingJob: true);
        
        try {
            $queue->push($job);   
        } catch (Throwable $e) {
            
        }
        
        $this->assertTrue($job === $collection->get('job'));
    }
    
    public function testJobIsProcessedIgnoringDelay()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $job = (new Mock\CallableJob())->delay(1000000);
        
        $queue->push($job);

        $this->assertSame(1, $job->processed());
    }
    
    public function testJobIsProcessedIgnoringDuration()
    {
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $job = (new Mock\CallableJob())->duration(1000000);
        
        $queue->push($job);

        $this->assertSame(1, $job->processed());
    }
    
    public function testJobIsProcessedIgnoringEncryption()
    {
        $container = new Container();
        Helper::bindEncrypterToContainer($container);
        
        $queue = new SyncQueue(
            name: 'primary',
            jobProcessor: new JobProcessor($container),
        );
        
        $processableParam = new Mock\ProcessableParameter();
        
        $job = (new Mock\CallableJob(payload: ['foo']))
            ->parameter($processableParam)
            ->encrypt();
        
        $queue->push($job);
        
        $this->assertSame(1, $job->processed());
        $this->assertSame(['foo'], $processableParam->beforeProcessedJob()?->getPayload());
    }
}