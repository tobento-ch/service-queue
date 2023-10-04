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

/**
 * HasParameters
 */
trait HasParameters
{
    /**
     * @var null|ParametersInterface
     */
    protected null|ParametersInterface $parameters = null;
    
    /**
     * Returns the parameters.
     *
     * @return ParametersInterface
     */
    public function parameters(): ParametersInterface
    {
        if (is_null($this->parameters)) {
            $this->parameters = new Parameters();
        }
        
        return $this->parameters;
    }
    
    /**
     * Add a parameter.
     *
     * @param ParameterInterface $parameter
     * @return static $this
     */
    public function parameter(ParameterInterface $parameter): static
    {
        $this->parameters()->add($parameter);
        
        return $this;
    }
}