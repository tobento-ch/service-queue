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

namespace Tobento\Service\Queue;

use IteratorAggregate;
use JsonSerializable;
use Stringable;

/**
 * ParametersInterface
 */
interface ParametersInterface extends IteratorAggregate, JsonSerializable, Stringable
{
    /**
     * Add a new parameter.
     *
     * @param ParameterInterface $parameter
     * @return static $this
     */
    public function add(ParameterInterface $parameter): static;
    
    /**
     * Returns true if parameter exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
    
    /**
     * Returns the parameter by name or null if not exists.
     *
     * @param string $name
     * @return null|object
     */
    public function get(string $name): null|object;
    
    /**
     * Remove a parameter.
     *
     * @param string $name
     * @return static $this
     */
    public function remove(string $name): static;
    
    /**
     * Returns a new instance with the filtered parameters.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static;
    
    /**
     * Returns a new instance with the resources sorted.
     *
     * @param null|callable $callback If null, sorts by priority, highest first.
     * @return static
     */
    public function sort(null|callable $callback = null): static;
    
    /**
     * Returns the parameters.
     *
     * @return array<string, ParameterInterface>
     */
    public function all(): array;
}