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
use Tobento\Service\Queue\Parameters;
use Tobento\Service\Queue\ParametersInterface;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter;

class ParametersTest extends TestCase
{
    public function testThatImplementsParametersInterface()
    {
        $this->assertInstanceof(ParametersInterface::class, new Parameters());
    }
    
    public function testConstructMethod()
    {
        $params = new Parameters(
            new Parameter\Queue('primary'),
        );
        
        $this->assertTrue($params->has(Parameter\Queue::class));
    }
    
    public function testAddMethod()
    {
        $params = new Parameters();
        $params->add(new Parameter\Queue('primary'));
        
        $this->assertTrue($params->has(Parameter\Queue::class));
    }
    
    public function testHasMethod()
    {
        $params = new Parameters();
        $params->add(new Parameter\Queue('primary'));
        
        $this->assertTrue($params->has(Parameter\Queue::class));
        $this->assertFalse($params->has('foo'));
    }
    
    public function testGetMethod()
    {
        $params = new Parameters();
        $param = new Parameter\Queue('primary');
        $params->add($param);
        
        $this->assertSame($param, $params->get(Parameter\Queue::class));
        $this->assertSame(null, $params->get('foo'));
    }
    
    public function testRemoveMethod()
    {
        $params = new Parameters();
        $params->add(new Parameter\Queue('primary'));
        
        $this->assertTrue($params->has(Parameter\Queue::class));
        
        $params->remove(Parameter\Queue::class);
        $params->remove('foo');
        
        $this->assertFalse($params->has(Parameter\Queue::class));
    }
    
    public function testFilterMethod()
    {
        $params = new Parameters();
        $params->add(new Parameter\Queue('primary'));
        $params->add(new Parameter\Delay(60));
        
        $paramsNew = $params->filter(fn (ParameterInterface $param) => $param instanceof Parameter\Delay);
        
        $this->assertFalse($params === $paramsNew);
        $this->assertTrue($params->has(Parameter\Queue::class));
        $this->assertFalse($paramsNew->has(Parameter\Queue::class));
    }
    
    public function testSortMethodHighestFirst()
    {
        $params = new Parameters();
        $params->add(new Mock\PushableParameter(name: 'foo', priority: 1));
        $params->add(new Mock\PushableParameter(name: 'bar', priority: 3));
        $params->add(new Mock\PushableParameter(name: 'baz', priority: 2));
        
        $paramsNew = $params->sort();
        
        $this->assertFalse($params === $paramsNew);
        $this->assertSame(['foo', 'bar', 'baz'], array_keys($params->all()));
        $this->assertSame(['bar', 'baz', 'foo'], array_keys($paramsNew->all()));
    }
    
    public function testAllMethod()
    {
        $params = new Parameters();
        
        $this->assertCount(0, $params->all());
        
        $params->add(new Parameter\Queue('primary'));
        $params->add(new Parameter\Delay(60));
        
        $this->assertCount(2, $params->all());
    }

    public function testGetIteratorMethod()
    {
        $params = new Parameters();
        
        $params->add(new Parameter\Queue('primary'));
        $params->add(new Parameter\Delay(60));
        
        $this->assertCount(2, $params->getIterator());
    }
    
    public function testJsonSerializeMethod()
    {
        $params = new Parameters();
        
        $this->assertSame([], $params->jsonSerialize());
        
        $params->add(new Parameter\Queue('primary'));
        $params->add(new Parameter\Delay(60));
        
        $this->assertSame(
            [
                Parameter\Queue::class => ['name' => 'primary'],
                Parameter\Delay::class => ['seconds' => 60],
            ],
            $params->jsonSerialize()
        );
    }
    
    public function testJsonSerializeMethodWithNonClassName()
    {
        $params = new Parameters();
        
        $this->assertSame([], $params->jsonSerialize());
        
        $params->add(new Parameter\Queue('primary'));
        $params->add(new Mock\PoppableParameter(name: 'foo'));
        
        $this->assertSame(
            [
                Parameter\Queue::class => ['name' => 'primary'],
                'Tobento\Service\Queue\Test\Mock\PoppableParameter:foo' => ['name' => 'foo'],
            ],
            $params->jsonSerialize()
        );
    }
    
    public function testToStringMethod()
    {
        $params = new Parameters();
        
        $this->assertSame('[]', $params->__toString());
        
        $params->add(new Parameter\Queue('primary'));
        $params->add(new Parameter\Delay(60));

        $this->assertSame(
            json_encode([Parameter\Queue::class => ['name' => 'primary'], Parameter\Delay::class => ['seconds' => 60]]),
            $params->__toString()
        );
    }
}