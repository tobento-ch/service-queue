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

use Throwable;

/**
 * JobException
 */
class JobException extends QueueException
{
    /**
     * Create a new JobException.
     *
     * @param null|JobInterface $job
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected null|JobInterface $job = null,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Returns the job.
     *
     * @return null|JobInterface
     */
    public function job(): null|JobInterface
    {
        return $this->job;
    }
}