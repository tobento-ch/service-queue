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
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\JobProcessorInterface;
use Tobento\Service\Queue\JobHandler;
use Tobento\Service\Queue\JobHandlerInterface;
use Tobento\Service\Queue\CallableJobHandlerInterface;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\JobException;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\HasParameters;
use Tobento\Service\Container\Container;
use Tobento\Service\Collection\Collection;

class JobProcessorTest extends TestCase
{
    protected function createJobHandler(string $name): JobHandlerInterface
    {
        return new class($name) implements JobHandlerInterface {
            public function __construct(
                public string $name,
                public bool $processed = false,
            ) {}

            public function handleJob(JobInterface $job): void
            {
                $this->processed = true;
            }
        };
    }
    
    public function testThatImplementsJobProcessorInterface()
    {
        $this->assertInstanceof(
            JobProcessorInterface::class,
            new JobProcessor(container: new Container())
        );
    }

    public function testProcessJobMethodWithAddedJobHandler()
    {
        $processor = new JobProcessor(new Container());
        $jobHandler = $this->createJobHandler('foo');
        $processor->addJobHandler('foo', $jobHandler);
        
        $job = new Job(name: 'foo');
        
        $processor->processJob($job);
        
        $this->assertTrue($jobHandler->processed);
    }

    public function testProcessJobMethodWithJobAsCallableHandler()
    {
        $processor = new JobProcessor(new Container());
        
        $job = new class('foo') implements JobInterface, CallableJobHandlerInterface {
            use HasParameters;
            
            public function __construct(
                public string $name,
                public bool $processed = false,
            ) {}
            
            public function getCallableJobHandler(): callable
            {
                return [$this, 'handleJob'];
            }

            public function handleJob(JobInterface $job, Foo $foo): void
            {
                $this->processed = true;
            }

            public function getId(): string
            {
                return 'id';
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getPayload(): array
            {
                return [];
            }
        };
        
        $processor->processJob($job);
        
        $this->assertTrue($job->processed);
    }
    
    public function testProcessJobMethodWithJobAsJobHandler()
    {
        $processor = new JobProcessor(new Container());
        
        $job = new class('foo') implements JobInterface, JobHandlerInterface {
            use HasParameters;
            
            public function __construct(
                public string $name,
                public bool $processed = false,
            ) {}

            public function handleJob(JobInterface $job): void
            {
                $this->processed = true;
            }

            public function getId(): string
            {
                return 'id';
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getPayload(): array
            {
                return [];
            }
        };
        
        $processor->processJob($job);
        
        $this->assertTrue($job->processed);
    }
    
    public function testProcessJobMethodWithCallableHandler()
    {
        $processor = new JobProcessor(new Container());
        
        $job = new Mock\CallableJob();
        
        $processor->processJob($job);
        
        $this->assertSame(1, $job->processed());
    }
    
    public function testProcessJobMethodWithJobHandler()
    {
        $processor = new JobProcessor(new Container());
        
        $job = new Job(FooJobHandler::class);
        
        $processor->processJob($job);
        
        $this->assertTrue(FooJobHandler::$processed);
    }
    
    public function testProcessJobMethodThrowsJobExceptionWithoutHandler()
    {
        $this->expectException(JobException::class);
        
        $processor = new JobProcessor(new Container());
        
        $job = new Job('foo');
        
        $processor->processJob($job);
    }
    
    public function testBeforeProcessJobMethod()
    {
        $processor = new JobProcessor(new Container());
        
        $param = new Mock\ProcessableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        
        $processor->beforeProcessJob($job);
        
        $this->assertSame($job->getId(), $param->beforeProcessedJob()?->getId());
    }
    
    public function testAfterProcessJobMethod()
    {
        $processor = new JobProcessor(new Container());
        
        $param = new Mock\ProcessableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        
        $processor->afterProcessJob($job);
        
        $this->assertSame($job->getId(), $param->afterProcessedJob()?->getId());
    }
    
    public function testProcessPushingJobMethod()
    {
        $processor = new JobProcessor(new Container());
        
        $param = new Mock\PushableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $processor);
        
        $processor->processPushingJob($job, $queue);
        
        $this->assertSame($job->getId(), $param->pushedJob()?->getId());
    }
    
    public function testProcessPushingJobMethodHighestPriorityFirst()
    {
        $processor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $processor);
        $collection = new Collection();
        
        $job = (new Mock\CallableJob())
            ->parameter(new Mock\PushableParameter(name: '1', handler: function($job) use ($collection) {
                $collection->set(1, true);
                return $job;
            }, priority: 1))
            ->parameter(new Mock\PushableParameter(name: '3', handler: function($job) use ($collection) {
                $collection->set(3, true);
                return $job;
            }, priority: 3))
            ->parameter(new Mock\PushableParameter(name: '2', handler: function($job) use ($collection) {
                $collection->set(2, true);
                return $job;
            }, priority: 2));
        
        $processor->processPushingJob($job, $queue);
        
        $this->assertSame([3, 2, 1], array_keys($collection->all()));
    }
    
    public function testProcessPoppingJobMethod()
    {
        $processor = new JobProcessor(new Container());
        
        $param = new Mock\PoppableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $processor);
        
        $processor->processPoppingJob($job, $queue);
        
        $this->assertSame($job->getId(), $param->poppedJob()?->getId());
    }
    
    public function testProcessPoppingJobMethodHighestPriorityLast()
    {
        $processor = new JobProcessor(new Container());
        $queue = new InMemoryQueue(name: 'primary', jobProcessor: $processor);
        $collection = new Collection();
        
        $job = (new Mock\CallableJob())
            ->parameter(new Mock\PoppableParameter(name: '1', handler: function($job) use ($collection) {
                $collection->set(1, true);
                return $job;
            }, priority: 1))
            ->parameter(new Mock\PoppableParameter(name: '3', handler: function($job) use ($collection) {
                $collection->set(3, true);
                return $job;
            }, priority: 3))        
            ->parameter(new Mock\PoppableParameter(name: '2', handler: function($job) use ($collection) {
                $collection->set(2, true);
                return $job;
            }, priority: 2));
        
        $processor->processPoppingJob($job, $queue);
        
        $this->assertSame([1, 2, 3], array_keys($collection->all()));
    }
    
    public function testProcessFailedJobMethod()
    {
        $processor = new JobProcessor(new Container());
        
        $param = new Mock\FailableParameter();
        
        $job = (new Mock\CallableJob())->parameter($param);
        $exception = new \Exception('failed');
        
        $processor->processFailedJob($job, $exception);
        
        $this->assertSame($job->getId(), $param->failedJob()?->getId());
        $this->assertSame($exception, $param->failedException());
    }
}

class Foo
{
    public function name(): string
    {
        return 'foo';
    }
}

class FooJobHandler implements JobHandlerInterface {
    
    public static $processed = false;

    public function handleJob(JobInterface $job): void
    {
        static::$processed = true;
    }
}