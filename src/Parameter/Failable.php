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
 * Failable
 */
interface Failable
{
    /**
     * Returns the failed job handler.
     *
     * @return callable
     */
    public function getFailedJobHandler(): callable;
}