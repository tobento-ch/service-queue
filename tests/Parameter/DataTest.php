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
use Tobento\Service\Queue\Parameter\Data;
use Tobento\Service\Queue\ParameterInterface;
use JsonSerializable;

class DataTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Data([]);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Data(['key' => 'value']);
        
        $this->assertSame(['data' => ['key' => 'value']], $param->jsonSerialize());
    }
    
    public function testClassSpecificMethods()
    {
        $param = new Data(['key' => 'value']);
        
        $this->assertSame(['key' => 'value'], $param->data());
    }
    
    public function testGetMethod()
    {
        $param = new Data(['key' => 'value']);
        
        $this->assertSame('value', $param->get(key: 'key'));
        $this->assertSame(null, $param->get(key: 'foo'));
        $this->assertSame('value', $param->get(key: 'foo', default: 'value'));
    }
    
    public function testSetMethod()
    {
        $param = new Data(['key' => 'value']);
        $this->assertSame(['key' => 'value'], $param->data());
        
        $param->set(key: 'foo', value: 'bar');
        
        $this->assertSame(['key' => 'value', 'foo' => 'bar'], $param->data());
    }
}