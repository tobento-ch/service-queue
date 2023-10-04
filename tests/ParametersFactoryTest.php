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
use Tobento\Service\Queue\ParametersFactory;
use Tobento\Service\Queue\ParametersFactoryInterface;
use Tobento\Service\Queue\ParametersInterface;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter;
use Tobento\Service\Queue\ParametersException;

class ParametersFactoryTest extends TestCase
{
    public function testThatImplementsParametersFactoryInterface()
    {
        $this->assertInstanceof(ParametersFactoryInterface::class, new ParametersFactory());
    }
    
    public function testCreateFromArrayMethod()
    {
        $params = (new ParametersFactory())->createFromArray([
            Parameter\Queue::class => ['name' => 'foo'],
            Parameter\Delay::class => ['seconds' => 60],
        ]);
        
        $this->assertSame('foo', $params->get(Parameter\Queue::class)->name());
        $this->assertSame(60, $params->get(Parameter\Delay::class)->seconds());
    }
    
    public function testCreateFromArrayMethodWithNonClassName()
    {
        $params = (new ParametersFactory())->createFromArray([
            Parameter\Queue::class => ['name' => 'foo'],
            'Tobento\Service\Queue\Test\Mock\PoppableParameter:bar' => ['name' => 'bar'],
        ]);
        
        $this->assertSame('foo', $params->get(Parameter\Queue::class)->name());
        $this->assertSame('bar', $params->get('bar')->getName());
    }
    
    public function testCreateFromArrayMethodThrowsParametersExceptionOnFailure()
    {
        $this->expectException(ParametersException::class);
        
        $params = (new ParametersFactory())->createFromArray([
            Parameter\Queue::class => ['invalid' => 'foo'],
        ]);
    }
    
    public function testCreateFromJsonStringMethod()
    {
        $params = (new ParametersFactory())->createFromJsonString(json_encode([
            Parameter\Queue::class => ['name' => 'foo'],
            Parameter\Delay::class => ['seconds' => 60],
        ]));
        
        $this->assertSame('foo', $params->get(Parameter\Queue::class)->name());
        $this->assertSame(60, $params->get(Parameter\Delay::class)->seconds());
    }
    
    public function testCreateFromStringMethodWithNonClassName()
    {
        $params = (new ParametersFactory())->createFromJsonString(json_encode([
            Parameter\Queue::class => ['name' => 'foo'],
            'Tobento\Service\Queue\Test\Mock\PoppableParameter:bar' => ['name' => 'bar'],
        ]));
        
        $this->assertSame('foo', $params->get(Parameter\Queue::class)->name());
        $this->assertSame('bar', $params->get('bar')->getName());
    }
    
    public function testCreateFromStringMethodThrowsParametersExceptionOnFailure()
    {
        $this->expectException(ParametersException::class);
        
        $params = (new ParametersFactory())->createFromJsonString(json_encode([
            Parameter\Queue::class => ['invalid' => 'foo'],
        ]));
    }
}