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

namespace Tobento\Service\Queue\Test\Console;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Console\Test\TestCommand;
use Tobento\Service\Queue\Console\WorkCommand;
use Tobento\Service\Queue\Test\Mock;
use Tobento\Service\Queue\Worker;
use Tobento\Service\Queue\WorkerOptions;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Container\Container;
use Tobento\Service\Event\Events;

class WorkCommandTest extends TestCase
{
    public function testCommand()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
            new InMemoryQueue(name: 'secondary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push(new Mock\CallableJob());
        $queues->queue('secondary')->push(new Mock\CallableJob());
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: null,
        );
        
        $container->set(Worker::class, $worker);
        
        (new TestCommand(
            command: WorkCommand::class,
            input: [
                '--sleep' => '0',
                '--stop-when-empty' => null,
            ],
        ))
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testWithEvents()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
            new InMemoryQueue(name: 'secondary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push($fooJob = new Mock\CallableJob(id: 'foo'));
        $queues->queue('secondary')->push($barJob = new Mock\CallableJob(id: 'bar'));
        
        $worker = new Worker(
            queues: $queues,
            jobProcessor: $jobProcessor,
            failedJobHandler: null,
            eventDispatcher: new Events(),
        );
        
        $container->set(Worker::class, $worker);
        
        (new TestCommand(
            command: WorkCommand::class,
            input: [
                '--sleep' => '0',
                '--stop-when-empty' => null,
            ],
        ))
        ->expectsOutputToContain('Worker default starting')
        ->expectsOutputToContain(sprintf('Starting job %s with the id %s', $fooJob->getName(), $fooJob->getId()))
        ->expectsOutputToContain(sprintf('Finished job %s with the id %s', $fooJob->getName(), $fooJob->getId()))
        ->expectsOutputToContain(sprintf('Starting job %s with the id %s', $barJob->getName(), $barJob->getId()))
        ->expectsOutputToContain(sprintf('Finished job %s with the id %s', $barJob->getName(), $barJob->getId()))
        ->expectsOutputToContain('Worker default stopped')
        ->expectsExitCode(0)
        ->execute($container);
    }
}