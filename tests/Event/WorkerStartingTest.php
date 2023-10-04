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

namespace Tobento\Service\Queue\Test\Event;

use PHPUnit\Framework\TestCase;
use Tobento\Service\Queue\Event\WorkerStarting;
use Tobento\Service\Queue\WorkerOptions;
use Exception;

class WorkerStartingTest extends TestCase
{
    public function testEvent()
    {
        $options = new WorkerOptions();
        $event = new WorkerStarting(queue: 'name', options: $options);
        
        $this->assertSame('name', $event->queue());
        $this->assertSame($options, $event->options());
    }
}