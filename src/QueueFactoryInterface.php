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
 * QueueFactoryInterface
 */
interface QueueFactoryInterface
{
    /**
     * Create a new queue based on the configuration.
     *
     * @param string $name
     * @param array $config
     * @return QueueInterface
     * @throws QueueException
     */
    public function createQueue(string $name, array $config): QueueInterface;
}