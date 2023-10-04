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
use Tobento\Service\Queue\Event\JobStarting;
use Tobento\Service\Queue\Job;

class JobStartingTest extends TestCase
{
    public function testEvent()
    {
        $job = new Job('foo');
        $event = new JobStarting(job: $job);
        
        $this->assertSame($job, $event->job());
    }
}