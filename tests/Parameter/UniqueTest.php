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
use Psr\SimpleCache\CacheInterface;
use Tobento\Service\Queue\Parameter\Unique;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter\Processable;
use Tobento\Service\Queue\Parameter\Failable;
use Tobento\Service\Queue\Parameter\Delay;
use Tobento\Service\Queue\Parameter\Duration;
use Tobento\Service\Queue\Parameter\Failed;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\JobSkipException;
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
    
    public function testJobGetsQueuedOnce()
    {
        $container = new Container();
        $cache = Helper::createCache();
        $container->set(CacheInterface::class, $cache);
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor($container),
        );
        
        $job = (new Mock\CallableJob(id: 'foo'))->parameter(new Unique());

        $queue->push($job);
        $queue->push($job);
        $queue->push($job);
        
        $this->assertSame(1, $queue->size());
    }
    
    public function testJobCanBeRequeuedAfterJobProcessed()
    {
        $container = new Container();
        $cache = Helper::createCache();
        $container->set(CacheInterface::class, $cache);
        $jobProcessor = new JobProcessor($container);
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: $jobProcessor,
        );
        
        $job = (new Mock\CallableJob(id: 'foo'))->parameter(new Unique());

        $queue->push($job);
        $queue->push($job);
        $queue->push($job);
        
        $this->assertSame(1, $queue->size());
        
        $processedJob = $jobProcessor->afterProcessJob($queue->pop(), $queue);
        
        $this->assertSame(0, $queue->size());
        
        $queue->push($job);
        $queue->push($job);
        
        $this->assertSame(1, $queue->size());
    }
    
    public function testCacheItemGetsDeletedOnJobFailing()
    {
        $cache = Helper::createCache();
        $cache->set('job-unique:foo', 'job');
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $param = new Unique();
        $job = (new Mock\CallableJob(id: 'foo'))->queue('primary')->parameter($param);
        $param->getFailedJobHandler()($job, new \Exception('message'), $cache);
        
        $this->assertFalse($cache->has('job-unique:foo'));
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Unique(id: 'foo');
        
        $this->assertSame('foo', $param->id());
    }
}