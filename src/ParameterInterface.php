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
 * ParameterInterface
 */
interface ParameterInterface
{
    /**
     * Returns the value.
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * Returns the priority.
     *
     * @return int
     */
    public function getPriority(): int;
}