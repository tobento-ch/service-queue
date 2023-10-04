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
use Tobento\Service\Queue\Parameter\Monitor;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter\Processable;
use Tobento\Service\Queue\Test\Mock;

class MonitorTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Monitor();
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(Processable::class, $param);
    }
    
    public function testMonitoring()
    {
        $param = new Monitor();

        $job = (new Mock\CallableJob(id: 'foo'))->parameter($param);

        $param->getBeforeProcessJobHandler()($job);
        
        $this->assertSame(0, $job->parameters()->get(Monitor::class)?->runtimeInSeconds());
        $this->assertSame(0, $job->parameters()->get(Monitor::class)?->memoryUsage());
        
        $param->getAfterProcessJobHandler()($job);
        
        $this->assertTrue($job->parameters()->get(Monitor::class)?->runtimeInSeconds() > 0);
        $this->assertTrue($job->parameters()->get(Monitor::class)?->memoryUsage() >= 0);
    }
}