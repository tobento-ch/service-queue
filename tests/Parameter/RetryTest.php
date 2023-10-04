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
use Tobento\Service\Queue\Parameter\Retry;
use Tobento\Service\Queue\ParameterInterface;
use JsonSerializable;

class RetryTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Retry(max: 2);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Retry(max: 2, retried: 1);
        
        $this->assertSame(['max' => 2, 'retried' => 1], $param->jsonSerialize());
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Retry(max: 2, retried: 1);
        
        $this->assertSame(2, $param->max());
        $this->assertSame(1, $param->retried());
        $this->assertFalse($param->isMaxReached());
        
        $param->increment();
        $this->assertSame(2, $param->retried());
        $this->assertTrue($param->isMaxReached());
    }
}