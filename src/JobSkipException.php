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
 * JobSkipException
 */
class JobSkipException extends JobException
{
    /**
     * Create a new JobSkipException.
     *
     * @param null|JobInterface $job
     * @param bool $retry If to retry the job.
     * @param string $message The message
     * @param int $code
     * @param null|Throwable $previous
     */
    public function __construct(
        protected null|JobInterface $job = null,
        protected bool $retry = true,
        string $message = '',
        int $code = 0,
        null|Throwable $previous = null
    ) {
        parent::__construct($job, $message, $code, $previous);
    }
    
    /**
     * Returns true if the job should get retried, otherwise false.
     *
     * @return bool
     */
    public function retry(): bool
    {
        return $this->retry;
    }
}