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
use Tobento\Service\Queue\FailedJobHandlerFactory;
use Tobento\Service\Queue\FailedJobHandlerFactoryInterface;
use Tobento\Service\Queue\FailedJobHandlerInterface;
use Tobento\Service\Queue\Queues;
use Monolog\Logger;

class FailedJobHandlerFactoryTest extends TestCase
{
    public function testImplementsFailedJobHandlerFactoryInterface()
    {
        $this->assertInstanceof(
            FailedJobHandlerFactoryInterface::class,
            new FailedJobHandlerFactory()
        );
    }
    
    public function testCreateFailedJobHandler()
    {
        $factory = new FailedJobHandlerFactory();
                
        $this->assertInstanceof(
            FailedJobHandlerInterface::class,
            $factory->createFailedJobHandler(queues: null)
        );
    }
    
    public function testCreateFailedJobHandlerWithQueues()
    {
        $factory = new FailedJobHandlerFactory();
                
        $this->assertInstanceof(
            FailedJobHandlerInterface::class,
            $factory->createFailedJobHandler(queues: new Queues())
        );
    }
    
    public function testCreateFailedJobHandlerWithLogger()
    {
        $factory = new FailedJobHandlerFactory(logger: new Logger('name'));
                
        $this->assertInstanceof(
            FailedJobHandlerInterface::class,
            $factory->createFailedJobHandler(queues: null)
        );
    }
}