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
use Tobento\Service\Queue\Parameter\Duration;
use Tobento\Service\Queue\Parameter\Failed;
use Tobento\Service\Queue\Parameter\SecondsBeforeTimingOut;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter\Processable;
use Tobento\Service\Queue\Test\Mock;
use JsonSerializable;

class DurationTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Duration(seconds: 60);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
        $this->assertInstanceof(Processable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Duration(seconds: 60);
        
        $this->assertSame(['seconds' => 60], $param->jsonSerialize());
    }
    
    public function testAddsFailedParameterWhenTimeoutLimitExceeds()
    {
        $param = new Duration(seconds: 60);
        
        $job = (new Mock\CallableJob(id: 'foo'))
            ->parameter($param)
            ->parameter(new SecondsBeforeTimingOut(30));
        
        $this->assertFalse($job->parameters()->has(Failed::class));
        
        $param->getBeforeProcessJobHandler()($job);
        
        $this->assertSame(Failed::TIMEOUT_LIMIT, $job->parameters()->get(Failed::class)?->reason());
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Duration(seconds: 60);
        
        $this->assertSame(60, $param->seconds());
    }
}