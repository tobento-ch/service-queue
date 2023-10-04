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
 * Data
 */
class Data extends Parameter implements JsonSerializable
{
    /**
     * Create a new Data.
     *
     * @param array $data Any job data which can be serialized natively by json_encode()
     */
    public function __construct(
        protected array $data,
    ) {}
    
    /**
     * Returns the data.
     *
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }
    
    /**
     * Returns the value from the specified key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Returns the value from the specified key.
     *
     * @param string $key
     * @param mixed $default
     * @return static $this
     */
    public function set(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        
        return $this;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['data' => $this->data()];
    }
}