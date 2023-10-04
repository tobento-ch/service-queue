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
use Tobento\Service\Queue\Event\PoppingJobFailed;
use Exception;

class PoppingJobFailedTest extends TestCase
{
    public function testEvent()
    {
        $exception = new Exception();
        $event = new PoppingJobFailed(exception: $exception, queue: 'name');
        
        $this->assertSame($exception, $event->exception());
        $this->assertSame('name', $event->queue());
    }
}