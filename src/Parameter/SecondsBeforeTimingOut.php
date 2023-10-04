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

/**
 * The seconds before timing out.
 */
class SecondsBeforeTimingOut extends Parameter
{
    /**
     * Create a new SecondsBeforeTimingOut.
     *
     * @param int|float $seconds
     */
    public function __construct(
        protected int|float $seconds,
    ) {}
    
    /**
     * Returns the seconds.
     *
     * @return int|float
     */
    public function seconds(): int|float
    {
        return $this->seconds;
    }
}