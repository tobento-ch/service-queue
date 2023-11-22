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
 * QueuesInterface
 */
interface QueuesInterface
{
    /**
     * Returns the queue.
     *
     * @param string $name
     * @return QueueInterface
     */
    public function queue(string $name): QueueInterface;
    
    /**
     * Returns the queue if exists, otherwise null.
     *
     * @param string $name
     * @return null|QueueInterface
     * @throws QueueException
     */
    public function get(string $name): null|QueueInterface;
    
    /**
     * Returns true if queue exists, otherwise false.
     *
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool;
    
    /**
     * Returns all queue names.
     *
     * @return array
     */
    public function names(): array;
}