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

namespace Tobento\Service\Queue\Test\Parameter;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Queue\Parameter\Unique;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter\Processable;
use Tobento\Service\Queue\Parameter\Failable;
use Tobento\Service\Queue\Parameter\Delay;
use Tobento\Service\Queue\Parameter\Duration;
use Tobento\Service\Queue\Parameter\Failed;
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\Test\Mock;
use Tobento\Service\Queue\Test\Helper;
use Tobento\Service\Container\Container;
use JsonSerializable;

class UniqueTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Unique();
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
        $this->assertInstanceof(Processable::class, $param);
        $this->assertInstanceof(Failable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Unique(id: 'foo');
        
        $this->assertSame(['id' => 'foo'], $param->jsonSerialize());
    }
    
    public function testGetsRequeuedWithDelayIfJobIsInProcess()
    {
        $cache = Helper::createCache();
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $queues = new Queues($queue);
        
        $param = new Unique();
        $job = (new Mock\CallableJob(id: 'foo'))->queue('primary')->parameter($param);
        $beforeJob = $param->getBeforeProcessJobHandler()($job, $cache, $queues);
        
        $this->assertFalse($beforeJob->parameters()->has(Delay::class));
        $this->assertSame(0, $queue->size());
        
        // should be requeued and delayed as same job is already running:
        $param = new Unique();
        $job = (new Mock\CallableJob(id: 'foo'))->queue('primary')->parameter($param);
        $beforeJob = $param->getBeforeProcessJobHandler()($job, $cache, $queues);
        
        $this->assertNull($beforeJob);
        $this->assertSame(1, $queue->size());
        $poppedJob = $queue->pop();
        $this->assertSame(30, $poppedJob?->parameters()->get(Delay::class)?->seconds());
        $this->assertSame(Failed::UNIQUE, $poppedJob?->parameters()->get(Failed::class)?->reason());
        $this->assertTrue($cache->has('job-processing:foo'));
        
        // after process job, cache item gets deleted:
        $afterJob = $param->getAfterProcessJobHandler()($poppedJob, $cache);
        $this->assertFalse($cache->has('job-processing:foo'));
    }
    
    public function testGetsRequeuedWithDelayIfJobIsInProcessWithSpecifiedParams()
    {
        $cache = Helper::createCache();
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $queues = new Queues($queue);
        
        $param = new Unique(id: 'bar', delayInSeconds: 45);
        $job = (new Mock\CallableJob(id: 'foo'))->queue('primary')->parameter($param);
        $beforeJob = $param->getBeforeProcessJobHandler()($job, $cache, $queues);
        
        $this->assertFalse($beforeJob->parameters()->has(Delay::class));
        $this->assertSame(0, $queue->size());
        
        // should be requeued and delayed as same job is already running:
        $param = new Unique(id: 'bar', delayInSeconds: 45);
        $job = (new Mock\CallableJob(id: 'baz'))->queue('primary')->parameter($param);
        $beforeJob = $param->getBeforeProcessJobHandler()($job, $cache, $queues);
        
        $this->assertNull($beforeJob);
        $this->assertSame(1, $queue->size());
        $poppedJob = $queue->pop();
        $this->assertSame(45, $poppedJob?->parameters()->get(Delay::class)?->seconds());
        $this->assertSame(Failed::UNIQUE, $poppedJob?->parameters()->get(Failed::class)?->reason());
        
        $this->assertTrue($cache->has('job-processing:bar'));
        
        // after process job, cache item gets deleted:
        $afterJob = $param->getAfterProcessJobHandler()($poppedJob, $cache);
        $this->assertFalse($cache->has('job-processing:bar'));
    }
    
    public function testUsesDurationParamForDelayIfExists()
    {
        $cache = Helper::createCache();
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $queues = new Queues($queue);
        
        $param = new Unique();
        $job = (new Mock\CallableJob(id: 'foo'))->queue('primary')->parameter($param);
        $beforeJob = $param->getBeforeProcessJobHandler()($job, $cache, $queues);
        
        $this->assertFalse($beforeJob->parameters()->has(Delay::class));
        $this->assertSame(0, $queue->size());
        
        // should be requeued and delayed as same job is already running:
        $param = new Unique();
        $job = (new Mock\CallableJob(id: 'foo'))
            ->queue('primary')
            ->duration(10)
            ->parameter($param);
        
        $beforeJob = $param->getBeforeProcessJobHandler()($job, $cache, $queues);

        $this->assertSame(10, $queue->pop()?->parameters()->get(Delay::class)?->seconds());
    }
    
    public function testCacheItemGetsDeletedOnJobFailing()
    {
        $cache = Helper::createCache();
        $cache->set('job-processing:foo', 'job');
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $queues = new Queues($queue);
        
        $param = new Unique();
        $job = (new Mock\CallableJob(id: 'foo'))->queue('primary')->parameter($param);
        $param->getFailedJobHandler()($job, null, $cache);
        
        $this->assertFalse($cache->has('job-processing:foo'));
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Unique(id: 'foo');
        
        $this->assertSame('foo', $param->id());
    }
}