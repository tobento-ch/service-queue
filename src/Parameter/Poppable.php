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
 * Poppable
 */
interface Poppable
{
    /**
     * Returns the popping job handler.
     *
     * @return callable
     */
    public function getPoppingJobHandler(): callable;
}