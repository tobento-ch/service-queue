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
use Tobento\Service\Queue\FailedJobHandler;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\JobSkipException;
use Tobento\Service\Container\Container;
use Psr\Container\ContainerInterface;
use Exception;
use Throwable;

class FailedJobHandlerTest extends TestCase
{
    public function testImplementsJobFailedJobHandlerInterface()
    {
        $this->assertInstanceof(FailedJobHandlerInterface::class, new FailedJobHandler());
    }
    
    public function testHandlesFailedJob()
    {
        $handler = new FailedJobHandler();
        
        $handler->handleFailedJob(job: new Mock\CallableJob(), e: new Exception('message'));
        
        $this->assertTrue(true);
    }
    
    public function testJobIsRetriedOnFailedJob()
    {
        $jobProcessor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);
        $handler = new FailedJobHandler(queues: $queues);
                
        $this->assertSame(0, $queue->size());
        
        $job = (new Mock\CallableJob())->queue('primary')->retry(max: 3);
        
        $handler->handleFailedJob(job: $job, e: new Exception('message'));
        
        $this->assertSame(1, $queue->size());
    }
    
    public function testJobIsRetriedOnJobSkipExceptionIfWantsRetry()
    {
        $jobProcessor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);
        $handler = new FailedJobHandler(queues: $queues);
                
        $this->assertSame(0, $queue->size());
        
        $job = (new Mock\CallableJob())->queue('primary');
        
        $handler->handleFailedJob(job: $job, e: new JobSkipException(retry: true));
        
        $this->assertSame(1, $queue->size());
    }
    
    public function testJobIsNotRetriedOnJobSkipException()
    {
        $jobProcessor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);
        $handler = new FailedJobHandler(queues: $queues);
                
        $this->assertSame(0, $queue->size());
        
        $job = (new Mock\CallableJob())->queue('primary');
        
        $handler->handleFailedJob(job: $job, e: new JobSkipException(retry: false));
        
        $this->assertSame(0, $queue->size());
    }
    
    public function testJobIsNotRepushedIfQueueDoesNotExistsAndFinallyFailedMethodIsCalled()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);
        
        $handler = new class($container, $queues) extends FailedJobHandler {
            public function __construct(
                protected ContainerInterface $container,
                protected null|QueuesInterface $queues = null,
            ) {}
            protected function finallyFailed(JobInterface $job, Throwable $e): void
            {
                $this->container->set('failedJob', $job);
            }
        };
        
        $this->assertSame(0, $queue->size());
        
        $job = (new Mock\CallableJob())
            ->queue('secondary')
            ->retry(max: 3);
        
        $handler->handleFailedJob(job: $job, e: new Exception('message'));
        
        $this->assertSame(0, $queue->size());
        $this->assertTrue($container->has('failedJob'));
    }
    
    public function testJobIsNotRepushedIfQueueParameterDoesNotExistsAndFinallyFailedMethodIsCalled()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);

        $handler = new class($container, $queues) extends FailedJobHandler {
            public function __construct(
                protected ContainerInterface $container,
                protected null|QueuesInterface $queues = null,
            ) {}
            protected function finallyFailed(JobInterface $job, Throwable $e): void
            {
                $this->container->set('failedJob', $job);
            }
        };
        
        $this->assertSame(0, $queue->size());
        
        $job = (new Mock\CallableJob())
            ->retry(max: 3);
        
        $handler->handleFailedJob(job: $job, e: new Exception('message'));
        
        $this->assertSame(0, $queue->size());
        $this->assertTrue($container->has('failedJob'));
    }
    
    public function testJobFinallyFailedMethodIsCalledWhenNoRetriedAtAll()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);

        $handler = new class($container, $queues) extends FailedJobHandler {
            public function __construct(
                protected ContainerInterface $container,
                protected null|QueuesInterface $queues = null,
            ) {}
            protected function finallyFailed(JobInterface $job, Throwable $e): void
            {
                $this->container->set('failedJob', $job);
            }
        };
        
        $job = new Mock\CallableJob();
        $handler->handleFailedJob(job: $job, e: new \Exception('message'));
        $this->assertTrue($container->has('failedJob'));
    }
}