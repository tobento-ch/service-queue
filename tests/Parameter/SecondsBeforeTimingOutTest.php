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
use Tobento\Service\Queue\Parameter\SecondsBeforeTimingOut;
use Tobento\Service\Queue\ParameterInterface;

class SecondsBeforeTimingOutTest extends TestCase
{
    public function testThatImplementsInterfaces()
    {
        $param = new SecondsBeforeTimingOut(seconds: 20);
        
        $this->assertInstanceof(ParameterInterface::class, $param);
    }
    
    public function testClassSpecificMethods()
    {
        $param = new SecondsBeforeTimingOut(seconds: 20);
        
        $this->assertSame(20, $param->seconds());
        
        $param = new SecondsBeforeTimingOut(seconds: 20.5);
        
        $this->assertSame(20.5, $param->seconds());
    }
}