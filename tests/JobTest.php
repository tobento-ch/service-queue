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
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\ParametersInterface;
use Tobento\Service\Queue\Parameters;
use Tobento\Service\Queue\Parameter;

class JobTest extends TestCase
{
    public function testThatImplementsJobInterface()
    {
        $this->assertInstanceof(JobInterface::class, new Job(name: 'foo'));
    }
    
    public function testGetIdMethod()
    {
        $this->assertTrue(strlen((new Job(name: 'foo'))->getId()) > 10);
        
        $this->assertSame('id', (new Job(name: 'foo', id: 'id'))->getId());
    }
    
    public function testGetNameMethod()
    {
        $this->assertSame('foo', (new Job(name: 'foo'))->getName());
    }
    
    public function testGetPayloadMethod()
    {
        $this->assertSame([], (new Job(name: 'foo'))->getPayload());
        
        $this->assertSame(['value'], (new Job(name: 'foo', payload: ['value']))->getPayload());
    }
    
    public function testParametersMethod()
    {
        $this->assertInstanceof(ParametersInterface::class, (new Job(name: 'foo'))->parameters());
        
        $params = new Parameters();
        $this->assertSame($params, (new Job(name: 'foo', parameters: $params))->parameters());
    }
    
    public function testParameterMethod()
    {
        $job = (new Job(name: 'foo'))
            ->parameter(new Parameter\Queue('name'));
        
        $this->assertTrue($job->parameters()->has(Parameter\Queue::class));
    }
    
    public function testParameterMethods()
    {
        $job = (new Job(name: 'foo'))
            ->queue(name: 'secondary')
            ->data(['key' => 'value'])
            ->duration(seconds: 10)
            ->retry(max: 2)
            ->delay(seconds: 5)
            ->unique()
            ->priority(100)
            ->pushing(function() {})
            ->encrypt();
        
        $this->assertTrue($job->parameters()->has(Parameter\Queue::class));
        $this->assertTrue($job->parameters()->has(Parameter\Data::class));
        $this->assertTrue($job->parameters()->has(Parameter\Duration::class));
        $this->assertTrue($job->parameters()->has(Parameter\Retry::class));
        $this->assertTrue($job->parameters()->has(Parameter\Delay::class));
        $this->assertTrue($job->parameters()->has(Parameter\Unique::class));
        $this->assertTrue($job->parameters()->has(Parameter\Priority::class));
        $this->assertTrue($job->parameters()->has(Parameter\Pushing::class));
        $this->assertTrue($job->parameters()->has(Parameter\Encrypt::class));
    }
}