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
 * Processable
 */
interface Processable
{
    /**
     * Returns the before process job handler.
     *
     * @return null|callable
     */
    public function getBeforeProcessJobHandler(): null|callable;
    
    /**
     * Returns the after process job handler.
     *
     * @return null|callable
     */
    public function getAfterProcessJobHandler(): null|callable;
}