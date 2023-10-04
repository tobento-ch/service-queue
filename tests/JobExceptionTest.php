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
use Tobento\Service\Queue\JobException;
use Tobento\Service\Queue\Job;

class JobExceptionTest extends TestCase
{
    public function testException()
    {
        $this->assertSame(null, (new JobException())->job());
    }
    
    public function testExceptionWithJob()
    {
        $job = new Job('name');
        
        $this->assertSame($job, (new JobException(job: $job))->job());
    }
}