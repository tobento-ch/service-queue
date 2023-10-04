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
use Tobento\Service\Queue\Event\WorkerStopped;
use Tobento\Service\Queue\WorkerOptions;
use Exception;

class WorkerStoppedTest extends TestCase
{
    public function testEvent()
    {
        $options = new WorkerOptions();
        $event = new WorkerStopped(status: 1, queue: 'name', options: $options);
        
        $this->assertSame(1, $event->status());
        $this->assertSame('name', $event->queue());
        $this->assertSame($options, $event->options());
    }
}