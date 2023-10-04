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
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Queue\QueueInterface;

class NullQueueTest extends TestCase
{
    public function testThatImplementsQueueInterface()
    {
        $queue = new NullQueue(name: 'primary');
        
        $this->assertInstanceof(QueueInterface::class, $queue);
    }
    
    public function testNameAndPriorityMethods()
    {
        $queue = new NullQueue(
            name: 'primary',
            priority: 150,
        );
        
        $this->assertSame('primary', $queue->name());
        $this->assertSame(150, $queue->priority());
    }
    
    public function testPushMethod()
    {
        $queue = new NullQueue(name: 'primary');
        
        $job = new Mock\CallableJob(id: 'foo');
            
        $jobId = $queue->push($job);

        $this->assertSame('foo', $jobId);
    }
    
    public function testPopMethod()
    {
        $queue = new NullQueue(name: 'primary');
        
        $foo = new Mock\CallableJob();
        $queue->push($foo);
        
        $this->assertSame(null, $queue->pop());
    }
    
    public function testGetJobMethod()
    {
        $queue = new NullQueue(name: 'primary');
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame(null, $queue->getJob(id: 'bar'));
    }
    
    public function testGetAllJobsMethod()
    {
        $queue = new NullQueue(name: 'primary');
        
        $this->assertSame([], $queue->getAllJobs());
    }
    
    public function testSizeMethod()
    {
        $queue = new NullQueue(name: 'primary');
        
        $this->assertSame(0, $queue->size());
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);
        
        $this->assertSame(0, $queue->size());
    }
    
    public function testClearMethod()
    {
        $queue = new NullQueue(name: 'primary');
        
        $foo = new Mock\CallableJob(id: 'foo');
        $queue->push($foo);

        $this->assertTrue($queue->clear());
        $this->assertSame(0, $queue->size());
    }
}