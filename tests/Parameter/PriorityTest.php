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
use Tobento\Service\Queue\Parameter\Priority;
use Tobento\Service\Queue\ParameterInterface;
use JsonSerializable;

class PriorityTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Priority(100);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Priority(100);
        
        $this->assertSame(['priority' => 100], $param->jsonSerialize());
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Priority(100);
        
        $this->assertSame(100, $param->priority());
    }
}