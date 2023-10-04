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
 * FailedJobHandlerFactoryInterface
 */
interface FailedJobHandlerFactoryInterface
{
    /**
     * Create a new failed job handler.
     *
     * @param null|QueuesInterface $queues
     * @return FailedJobHandlerInterface
     */
    public function createFailedJobHandler(null|QueuesInterface $queues): FailedJobHandlerInterface;
}