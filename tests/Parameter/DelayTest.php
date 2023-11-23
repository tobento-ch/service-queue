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
use Tobento\Service\Queue\Parameter\Delay;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter\Failable;
use Tobento\Service\Queue\Test\Mock;
use JsonSerializable;

class DelayTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Delay(seconds: 60);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
        $this->assertInstanceof(Failable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Delay(seconds: 60);
        
        $this->assertSame(['seconds' => 60], $param->jsonSerialize());
    }
    
    public function testRemovesDelayOnFailedJob()
    {
        $param = new Delay(seconds: 60);
        
        $job = (new Mock\CallableJob(id: 'foo'))->parameter($param);
        
        $this->assertTrue($job->parameters()->has(Delay::class));
        
        $param->getFailedJobHandler()($job, new \Exception('message'));
        
        $this->assertFalse($job->parameters()->has(Delay::class));
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Delay(seconds: 60);
        
        $this->assertSame(60, $param->seconds());
    }
}