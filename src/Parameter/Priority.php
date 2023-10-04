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
 * The job priority: higher priority gets processed first.
 */
class Priority extends Parameter implements JsonSerializable
{
    /**
     * Create a new Priority.
     *
     * @param int $priority
     */
    public function __construct(
        protected int $priority,
    ) {}
    
    /**
     * Returns the job priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['priority' => $this->priority()];
    }
}