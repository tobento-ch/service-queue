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
use Tobento\Service\Queue\Event\JobFailed;
use Tobento\Service\Queue\Job;
use Exception;

class JobFailedTest extends TestCase
{
    public function testEvent()
    {
        $job = new Job('foo');
        $exception = new Exception();
        $event = new JobFailed(job: $job, exception: $exception);
        
        $this->assertSame($job, $event->job());
        $this->assertSame($exception, $event->exception());
    }
}