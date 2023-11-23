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
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\JobSkipException;
use Tobento\Service\Container\Container;
use Psr\Log\LogLevel;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Exception;

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
    
    public function testJobIsNotRepushedIfQueueDoesNotExistsAndGetsLogged()
    {
        $logger = new Logger('name');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        
        $jobProcessor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);
        $handler = new FailedJobHandler(queues: $queues, logger: $logger);
                
        $this->assertSame(0, $queue->size());
        
        $job = (new Mock\CallableJob())
            ->queue('secondary')
            ->retry(max: 3);
        
        $handler->handleFailedJob(job: $job, e: new Exception('message'));
        
        $this->assertSame(0, $queue->size());
        $this->assertTrue($testHandler->hasRecordThatContains('message', LogLevel::ERROR));
    }
    
    public function testJobIsNotRepushedIfQueueParameterDoesNotExistsAndGetsLogged()
    {
        $logger = new Logger('name');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        
        $jobProcessor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);
        $handler = new FailedJobHandler(queues: $queues, logger: $logger);
                
        $this->assertSame(0, $queue->size());
        
        $job = (new Mock\CallableJob())
            ->retry(max: 3);
        
        $handler->handleFailedJob(job: $job, e: new Exception('message'));
        
        $this->assertSame(0, $queue->size());
        $this->assertTrue($testHandler->hasRecordThatContains('message', LogLevel::ERROR));
    }
    
    public function testJobIsLogged()
    {
        $logger = new Logger('name');
        $testHandler = new TestHandler();
        $logger->pushHandler($testHandler);
        
        $jobProcessor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor);
        $queues = new Queues($queue);
        $handler = new FailedJobHandler(queues: $queues, logger: $logger);
        
        $job = new Mock\CallableJob();
        $handler->handleFailedJob(job: $job, e: new \Exception('message'));
        $this->assertTrue($testHandler->hasRecordThatContains('message', LogLevel::ERROR));
    }
}