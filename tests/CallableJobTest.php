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
use Tobento\Service\Queue\CallableJob;
use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\ParametersInterface;
use Tobento\Service\Queue\Parameters;
use Tobento\Service\Queue\Parameter;

class CallableJobTest extends TestCase
{
    public function testThatImplementsJobInterface()
    {
        $this->assertInstanceof(JobInterface::class, new SampleJob());
    }
    
    public function testGetIdMethod()
    {
        $this->assertTrue(strlen((new SampleJob())->getId()) > 10);
    }
    
    public function testGetNameMethod()
    {
        $this->assertSame(SampleJob::class, (new SampleJob())->getName());
    }
    
    public function testGetPayloadMethod()
    {
        $this->assertSame([], (new SampleJob())->getPayload());
    }
    
    public function testParametersMethod()
    {
        $this->assertInstanceof(ParametersInterface::class, (new SampleJob())->parameters());
    }
    
    public function testParameterMethod()
    {
        $job = (new SampleJob())
            ->parameter(new Parameter\Queue('name'));
        
        $this->assertTrue($job->parameters()->has(Parameter\Queue::class));
    }
    
    public function testParameterMethods()
    {
        $job = (new SampleJob())
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

final class SampleJob extends CallableJob
{
    public function __construct(
        private null|MessageInterface $message = null,
    ) {}

    public function handleJob(
        JobInterface $job,
        SomeService $service,
    ): void {
        //
    }
    
    public function getPayload(): array
    {
        return [];
    }
}

final class SomeService {}