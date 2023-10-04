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
use Tobento\Service\Container\Container;

class WorkerLongProcessesTest extends TestCase
{
    public function setUp(): void
    {
        if (getenv('TEST_TOBENTO_QUEUE_SKIP_LONG_PROCESSES')) {
            $this->markTestSkipped('Long processes tests are disabled');
        }
    }
    
    public function testWorkerSleepsWhenQueueIsEmpty()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push($fooJob = new Mock\CallableJob());
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: null,
        );
        
        $startTime = microtime(true);
        
        $status = $worker->run('primary', new WorkerOptions(sleep: 1, stopWhenEmpty: true));
                
        $runtime = microtime(true) - $startTime;
        
        $this->assertSame(Worker::STATUS_SUCCESS, $status);
        $this->assertTrue($runtime >= 1);
    }
    
    public function testWorkerStopsOnTimeout()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
        );
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: null,
        );
        
        $startTime = microtime(true);
        
        $status = $worker->run('primary', new WorkerOptions(timeout: 1, sleep: 0));

        $runtime = microtime(true) - $startTime;
        
        $this->assertSame(Worker::STATUS_SUCCESS, $status);
        $this->assertTrue($runtime <= 1.1);
    }
}