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

use Tobento\Service\Queue\Parameter;
use Closure;

/**
 * Default parameters methods.
 */
trait InteractsWithParameters
{
    /**
     * Specify any additional data.
     *
     * @param array $data
     * @return static $this
     */
    public function data(array $data): static
    {
        $this->parameters()->add(new Parameter\Data(data: $data));
        
        return $this;
    }
    
    /**
     * Specify the delay in seconds.
     *
     * @param int $seconds
     * @return static $this
     */
    public function delay(int $seconds): static
    {
        $this->parameters()->add(new Parameter\Delay(seconds: $seconds));
        
        return $this;
    }
    
    /**
     * Specify the approximate duration the job needs to process.
     *
     * @param int $seconds
     * @return static $this
     */
    public function duration(int $seconds): static
    {
        $this->parameters()->add(new Parameter\Duration(seconds: $seconds));
        
        return $this;
    }
    
    /**
     * Encrypts the job.
     *
     * @param int $priority
     * @return static $this
     */
    public function encrypt(): static
    {
        $this->parameters()->add(new Parameter\Encrypt());
        
        return $this;
    }
    
    /**
     * Specify the job priority: higher priority gets processed first.
     *
     * @param int $priority
     * @return static $this
     */
    public function priority(int $priority): static
    {
        $this->parameters()->add(new Parameter\Priority($priority));
        
        return $this;
    }
    
    /**
     * Specify the job priority: higher priority gets processed first.
     *
     * @param Closure $handler
     * @param int $priority
     * @return static $this
     */
    public function pushing(Closure $handler, int $priority = 0): static
    {
        $this->parameters()->add(new Parameter\Pushing($handler, $priority));
        
        return $this;
    }
    
    /**
     * Specify the queue to push on.
     *
     * @param string $name The queue name.
     * @return static $this
     */
    public function queue(string $name): static
    {
        $this->parameters()->add(new Parameter\Queue(name: $name));
        
        return $this;
    }
    
    /**
     * Specify the max number of retries.
     *
     * @param int $max
     * @return static $this
     */
    public function retry(int $max = 3): static
    {
        $this->parameters()->add(new Parameter\Retry(max: $max));
        
        return $this;
    }
    
    /**
     * Specify if the job is unique.
     *
     * @param null|string $id A unique id. If null it uses the job id.
     * @param int The job delay in seconds as fallback if job has no duration parameter.
     * @return static $this
     */
    public function unique(null|string $id = null, int $delayInSeconds = 30): static
    {
        $this->parameters()->add(new Parameter\Unique(id: $id, delayInSeconds: $delayInSeconds));
        
        return $this;
    }
}