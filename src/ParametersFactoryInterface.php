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
 * ParametersFactoryInterface
 */
interface ParametersFactoryInterface
{
    /**
     * Creates parameters from array.
     *
     * @param array $parameters
     * @return ParametersInterface
     * @throws ParametersException
     */
    public function createFromArray(array $parameters): ParametersInterface;
    
    /**
     * Creates parameters from JSON string.
     *
     * @param string $json
     * @return ParametersInterface
     * @throws ParametersException
     */
    public function createFromJsonString(string $json): ParametersInterface;
}