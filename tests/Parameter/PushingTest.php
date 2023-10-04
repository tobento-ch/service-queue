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
use Tobento\Service\Queue\Parameter\Pushing;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter\Pushable;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\NullQueue;
use Tobento\Service\Container\Container;
use Tobento\Service\Queue\Test\Mock;

class PushingTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Pushing(fn (JobInterface $job) => $job);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(Pushable::class, $param);
    }
    
    public function testPushing()
    {
        $container = new Container();
        
        $param = new Pushing(function(JobInterface $job) use ($container) {
            $container->set('job', $job);
            return $job;
        });
        
        $job = (new Mock\CallableJob(id: 'foo'))->parameter($param);
        
        $param->getPushingJobHandler()($job, new NullQueue('name'), $container);
        
        $this->assertTrue($container->has('job'));
    }
}