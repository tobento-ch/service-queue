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
use Tobento\Service\Queue\Console\ClearCommand;
use Tobento\Service\Queue\Test\Mock;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\Queues;
use Tobento\Service\Queue\QueuesInterface;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Container\Container;

class ClearCommandTest extends TestCase
{
    public function testClearAllQueues()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
            new InMemoryQueue(name: 'secondary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push(new Mock\CallableJob());
        $queues->queue('secondary')->push(new Mock\CallableJob());
        
        $container->set(QueuesInterface::class, $queues);
        
        (new TestCommand(command: ClearCommand::class))
            ->expectsOutput('Jobs cleared from queue primary')
            ->expectsOutput('Jobs cleared from queue secondary')
            ->expectsExitCode(0)
            ->execute($container);
    }
    
    public function testClearSpecificQueues()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
            new InMemoryQueue(name: 'secondary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push(new Mock\CallableJob());
        $queues->queue('secondary')->push(new Mock\CallableJob());
        
        $container->set(QueuesInterface::class, $queues);
        
        (new TestCommand(
            command: ClearCommand::class,
            input: [
                '--queue' => ['primary'],
            ],            
        ))
        ->expectsOutput('Jobs cleared from queue primary')
        ->doesntExpectOutput('Jobs cleared from queue secondary')
        ->expectsExitCode(0)
        ->execute($container);
    }
    
    public function testQueueNotFound()
    {
        $container = new Container();
        $jobProcessor = new JobProcessor($container);

        $queues = new Queues(
            new InMemoryQueue(name: 'primary', jobProcessor: $jobProcessor),
        );
        
        $queues->queue('primary')->push(new Mock\CallableJob());
        
        $container->set(QueuesInterface::class, $queues);
        
        (new TestCommand(
            command: ClearCommand::class,
            input: [
                '--queue' => ['foo'],
            ],            
        ))
        ->expectsOutput('Queue foo not found to clear jobs')
        ->expectsExitCode(0)
        ->execute($container);
    }
}