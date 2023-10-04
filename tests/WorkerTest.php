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
use Tobento\Service\Queue\Worker;
use Tobento\Service\Queue\WorkerOptions;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\Event;
use Tobento\Service\Container\Container;
use Tobento\Service\Event\Events;

class WorkerTest extends TestCase
{
    public function testJobsAreProcessed()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
            new InMemoryQueue(name: 'secondary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push($fooJob = new Mock\CallableJob());
        $queues->queue('secondary')->push($barJob = new Mock\CallableJob());
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: null,
        );
        
        $status = $worker->run(null, new WorkerOptions(sleep: 0, stopWhenEmpty: true));
        
        $this->assertSame(Worker::STATUS_SUCCESS, $status);
        $this->assertSame(1, $fooJob->processed());
        $this->assertSame(1, $barJob->processed());
    }
    
    public function testJobsAreProcessedWithSpecificQueue()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push($fooJob = new Mock\CallableJob());
        $queues->queue('primary')->push($barJob = new Mock\CallableJob());
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: null,
        );
        
        $status = $worker->run('primary', new WorkerOptions(sleep: 0, stopWhenEmpty: true));
        
        $this->assertSame(Worker::STATUS_SUCCESS, $status);
        $this->assertSame(1, $fooJob->processed());
        $this->assertSame(1, $barJob->processed());
    }
    
    public function testJobsAreProcessedByQueuePriority()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'low', jobProcessor: $jobProcessor, priority: 50),
            new InMemoryQueue(name: 'high', jobProcessor: $jobProcessor, priority: 100),
        );
        
        $queues->queue('low')->push($lowJob = new Mock\CallableJob());
        $queues->queue('high')->push($highJob = new Mock\CallableJob());
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: null,
        );
        
        $status = $worker->run(null, new WorkerOptions(maxJobs: 1, sleep: 0, stopWhenEmpty: true));
        $this->assertSame(Worker::STATUS_SUCCESS, $status);
        $this->assertSame(0, $lowJob->processed());
        $this->assertSame(1, $highJob->processed());
        
        $status = $worker->run(null, new WorkerOptions(maxJobs: 1, sleep: 0, stopWhenEmpty: true));
        $this->assertSame(Worker::STATUS_SUCCESS, $status);
        $this->assertSame(1, $lowJob->processed());
    }
    
    public function testEvents()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push($job = new Mock\CallableJob());
        
        $events = new Events();

        $events->listen(function(Event\WorkerStarting $event) use ($container) {
            $container->set('starting:worker', true);
        });
        
        $events->listen(function(Event\WorkerStopped $event) use ($container) {
            $container->set('stopped:worker', true);
        });
        
        $events->listen(function(Event\JobStarting $event) use ($container) {
            $container->set('starting:job', $event->job());
        });
        
        $events->listen(function(Event\JobFinished $event) use ($container) {
            $container->set('finished:job', $event->job());
        });
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: $events,
        );
        
        $worker->run(null, new WorkerOptions(sleep: 0, stopWhenEmpty: true));
        
        $this->assertTrue($container->has('starting:worker'));
        $this->assertTrue($container->has('stopped:worker'));
        $this->assertTrue($job === $container->get('starting:job'));
        $this->assertTrue($job === $container->get('finished:job'));
    }
}