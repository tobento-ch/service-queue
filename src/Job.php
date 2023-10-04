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

class Job implements JobInterface
{
    use HasParameters;
    use InteractsWithParameters;
    
    /**
     * Create a new Job.
     *
     * @param string $name
     * @param array $payload
     * @param null|ParametersInterface $parameters
     * @param null|string $id
     */
    public function __construct(
        protected string $name,
        protected array $payload = [],
        null|ParametersInterface $parameters = null,
        protected null|string $id = null,
    ) {
        $this->parameters = $parameters;
    }
    
    /**
     * Returns the job id.
     *
     * @return string
     */
    public function getId(): string
    {
        if (is_null($this->id)) {
            $this->id = bin2hex(random_bytes(25));
        }
        
        return $this->id;
    }
    
    /**
     * Returns the job name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Set the payload.
     *
     * @param array $payload
     * @return static $this
     */
    public function payload(array $payload): static
    {
        $this->payload = $payload;
        return $this;
    }
    
    /**
     * Returns the payload.
     *
     * @return array Must be serializable natively by json_encode().
     */
    public function getPayload(): array
    {
        return $this->payload;
    }
}