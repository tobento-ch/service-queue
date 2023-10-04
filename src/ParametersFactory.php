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

use JsonException;
use Throwable;

/**
 * ParametersFactory
 */
class ParametersFactory implements ParametersFactoryInterface
{
    /**
     * Creates parameters from array.
     *
     * @param array $parameters
     * @return ParametersInterface
     * @throws ParametersException
     */
    public function createFromArray(array $parameters): ParametersInterface
    {
        $params = new Parameters();

        foreach($parameters as $name => $value) {
            
            if (str_contains($name, ':')) {
                [$name] = explode(':', $name, 2);
            }
            
            $params->add($this->createParameter($name, $value));
        }
        
        return $params;
    }
    
    /**
     * Creates parameters from JSON string.
     *
     * @param string $json
     * @return ParametersInterface
     * @throws ParametersException
     */
    public function createFromJsonString(string $json): ParametersInterface
    {
        try {
            $data = json_decode($json, true, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new ParametersException($e->getMessage(), $e->getCode(), $e);
        }
        
        if (!is_array($data)) {
            throw new ParametersException('Invalid parameters data: must be a valid array');
        }
        
        return $this->createFromArray($data);
    }
    
    /**
     * Create paramater from the specified name and value.
     *
     * @param class-string $name
     * @param mixed $value
     * @return ParameterInterface
     * @throws ParametersException
     */
    protected function createParameter(string $name, mixed $value): ParameterInterface
    {
        if (!is_array($value)) {
            throw new ParametersException(sprintf('Invalid parameter value for %s', $name));
        }        
        
        try {
            $parameter = new $name(...$value);
        } catch (Throwable $e) {
            throw new ParametersException($e->getMessage(), (int)$e->getCode(), $e);
        }
        
        if (! $parameter instanceof ParameterInterface) {
            throw new ParametersException(
                sprintf('Parameter needs to be an instanceof %s', ParameterInterface::class)
            );
        }
        
        return $parameter;
    }
}