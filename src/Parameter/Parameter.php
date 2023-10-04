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

use Tobento\Service\Queue\ParameterInterface;

/**
 * Abstract parameter.
 */
abstract class Parameter implements ParameterInterface
{
    /**
     * Returns the parameter name.
     *
     * @return string
     */
    public function getName(): string
    {
        return static::class;
    }
    
    /**
     * Returns the priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return 0;
    }
}