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

namespace Tobento\Service\Queue\Parameter;

use JsonSerializable;

/**
 * The reason for job failing.
 */
class Failed extends Parameter implements JsonSerializable
{
    /**
     * Job process failed because of job timed out.
     */
    public const TIMED_OUT = 0;
    
    /**
     * Job not processed as timeout limit exceeded to process the job.
     */    
    public const TIMEOUT_LIMIT = 1;
    
    /**
     * Job not processed as unique.
     */    
    public const UNIQUE = 2;
    
    /**
     * Create a new Failed.
     *
     * @param int $reason
     */
    public function __construct(
        protected int $reason,
    ) {}
    
    /**
     * Returns the reason code.
     *
     * @return int
     */
    public function reason(): int
    {
        return $this->reason;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['reason' => $this->reason()];
    }
}