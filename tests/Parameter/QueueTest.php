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
use Tobento\Service\Queue\Parameter\Queue;
use Tobento\Service\Queue\ParameterInterface;
use JsonSerializable;

class QueueTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Queue(name: 'foo');
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Queue(name: 'foo');
        
        $this->assertSame(['name' => 'foo'], $param->jsonSerialize());
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Queue(name: 'foo');
        
        $this->assertSame('foo', $param->name());
    }
}