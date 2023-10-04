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
 * Queue
 */
class Queue extends Parameter implements JsonSerializable
{
    /**
     * Create a new Queue.
     *
     * @param string $name The queue name.
     */
    public function __construct(
        protected string $name,
    ) {}
    
    /**
     * Returns the queue name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['name' => $this->name()];
    }
}