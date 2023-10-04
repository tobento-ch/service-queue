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
use Tobento\Service\Queue\Parameter\Encrypt;
use Tobento\Service\Queue\ParameterInterface;
use Tobento\Service\Queue\Parameter\Pushable;
use Tobento\Service\Queue\Parameter\Poppable;
use Tobento\Service\Queue\Parameter\Data;
use Tobento\Service\Queue\InMemoryQueue;
use Tobento\Service\Queue\JobProcessor;
use Tobento\Service\Queue\Test\Mock;
use Tobento\Service\Queue\Test\Helper;
use Tobento\Service\Container\Container;
use JsonSerializable;

class EncryptTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Encrypt();
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
        $this->assertInstanceof(Pushable::class, $param);
        $this->assertInstanceof(Poppable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Encrypt();
        
        $this->assertSame(['encrypt' => true], $param->jsonSerialize());
    }
    
    public function testEncrypting()
    {
        $param = new Encrypt();
        $dataParam = new Data(['key' => 'value']);
        
        $job = (new Mock\CallableJob(payload: ['key' => 'value'], id: 'foo'))
            ->parameter($param)
            ->parameter($dataParam);
        
        $queue = new InMemoryQueue(
            name: 'primary',
            jobProcessor: new JobProcessor(new Container()),
        );
        
        $encrypter = Helper::createEncrypter();
        
        $pushedJob = $param->getPushingJobHandler()($job, $queue, $encrypter);
        
        $this->assertFalse($job->getPayload() === $pushedJob->getPayload());
        $this->assertFalse($dataParam->data() === $pushedJob->parameters()->get(Data::class)->data());
        
        $poppedJob = $param->getPoppingJobHandler()($pushedJob, $queue, $encrypter);
        
        $this->assertTrue($job->getPayload() === $poppedJob->getPayload());
        $this->assertTrue($dataParam->data() === $poppedJob->parameters()->get(Data::class)->data());
    }    
}