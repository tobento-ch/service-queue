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

use ArrayIterator;
use Traversable;
use JsonSerializable;

/**
 * Parameters
 */
class Parameters implements ParametersInterface
{
    /**
     * @var array<string, ParameterInterface>
     */
    protected array $parameters = [];
    
    /**
     * Create a new Parameters.
     *
     * @param ParameterInterface ...$parameters
     */
    public function __construct(
        ParameterInterface ...$parameters,
    ) {
        foreach($parameters as $parameter) {
            $this->add($parameter);
        }
    }

    /**
     * Add a new parameter.
     *
     * @param ParameterInterface $parameter
     * @return static $this
     */
    public function add(ParameterInterface $parameter): static
    {
        $this->parameters[$parameter->getName()] = $parameter;
        
        return $this;
    }

    /**
     * Returns true if parameter exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->parameters);
    }
    
    /**
     * Returns the parameter by name or null if not exists.
     *
     * @param string $name
     * @return null|object
     */
    public function get(string $name): null|object
    {
        return $this->parameters[$name] ?? null;
    }
    
    /**
     * Remove a parameter.
     *
     * @param string $name
     * @return static $this
     */
    public function remove(string $name): static
    {
        unset($this->parameters[$name]);
        
        return $this;
    }
    
    /**
     * Returns a new instance with the filtered parameters.
     *
     * @param callable $callback
     * @return static
     */
    public function filter(callable $callback): static
    {
        $new = clone $this;
        $new->parameters = array_filter($this->parameters, $callback);
        return $new;
    }
    
    /**
     * Returns a new instance with the resources sorted.
     *
     * @param null|callable $callback If null, sorts by priority, highest first.
     * @return static
     */
    public function sort(null|callable $callback = null): static
    {
        if (is_null($callback))
        {
            $callback = fn(ParameterInterface $a, ParameterInterface $b): int
                => $b->getPriority() <=> $a->getPriority();
        }
        
        $new = clone $this;
        uasort($new->parameters, $callback);
        return $new;
    }
    
    /**
     * Returns the parameters.
     *
     * @return array<string, ParameterInterface>
     */
    public function all(): array
    {
        return $this->parameters;
    }
    
    /**
     * Get the iterator. 
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->all());
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $parameters = [];
        
        foreach($this->all() as $parameter) {
            if ($parameter instanceof JsonSerializable) {
                
                if ($parameter->getName() !== $parameter::class) {
                    $parameters[$parameter::class.':'.$parameter->getName()] = $parameter->jsonSerialize();
                } else {
                    $parameters[$parameter->getName()] = $parameter->jsonSerialize();
                }
            }
        }

        return $parameters;
    }
    
    /**
     * Returns the string representation of the parameters.
     *
     * @return string
     */
    public function __toString(): string
    {
        return json_encode($this->jsonSerialize());
    }
}