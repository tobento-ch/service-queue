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

namespace Tobento\Service\Queue\Test\Mock;

use Tobento\Service\Queue\CallableJob as Job;
use Tobento\Service\Queue\JobInterface;

final class CallableJob extends Job
{
    private int $processed = 0;
    private null|JobInterface $handledJob = null;
    
    public function __construct(
        protected array $payload = [],
        protected bool $failingJob = false,
        null|string $id = null,
    ) {
        $this->id = $id;
    }
    
    public function processed(): int
    {
        return $this->processed;
    }
    
    public function handledJob(): null|JobInterface
    {
        return $this->handledJob;
    }
    
    public function getPayload(): array
    {
        return $this->payload;
    }
    
    public function handleJob(JobInterface $job): void
    {
        if ($this->failingJob) {
            throw \Exception('failing job');
        }
        
        $this->handledJob = $job;
        $this->processed++;
    }
}