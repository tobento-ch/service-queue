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
use Tobento\Service\Queue\Parameter\Failed;
use Tobento\Service\Queue\ParameterInterface;
use JsonSerializable;

class FailedTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new Failed(Failed::TIMED_OUT);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
        $this->assertInstanceof(JsonSerializable::class, $param);
    }
    
    public function testJsonSerializeMethod()
    {
        $param = new Failed(Failed::TIMED_OUT);
        
        $this->assertSame(['reason' => Failed::TIMED_OUT], $param->jsonSerialize());
    }
    
    public function testClassSpecificMethods()
    {
        $this->assertSame(Failed::TIMED_OUT, (new Failed(Failed::TIMED_OUT))->reason());
        $this->assertSame(Failed::TIMEOUT_LIMIT, (new Failed(Failed::TIMEOUT_LIMIT))->reason());
        $this->assertSame(Failed::UNIQUE, (new Failed(Failed::UNIQUE))->reason());
    }
}