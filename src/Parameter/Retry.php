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
 * Retry
 */
class Retry extends Parameter implements JsonSerializable
{
    /**
     * Create a new Retry.
     *
     * @param int $max The max number of retries.
     * @param int $retried The number of times the job has been retried.
     */
    public function __construct(
        protected int $max = 3,
        protected int $retried = 0,
    ) {}
    
    /**
     * Returns the max number of retries.
     *
     * @return int
     */
    public function max(): int
    {
        return $this->max;
    }
    
    /**
     * Returns the number of times the job has been retried.
     *
     * @return int
     */
    public function retried(): int
    {
        return $this->retried;
    }
    
    /**
     * Increments the number of retries.
     *
     * @return static $this
     */
    public function increment(): static
    {
        $this->retried++;
        
        return $this;
    }
    
    /**
     * Returns true if the max is reached, otherwise false.
     *
     * @return bool
     */
    public function isMaxReached(): bool
    {
        return $this->retried() >= $this->max();
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['max' => $this->max(), 'retried' => $this->retried()];
    }
}