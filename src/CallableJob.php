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

abstract class CallableJob implements JobInterface, CallableJobHandlerInterface
{
    use HasParameters;
    use InteractsWithParameters;
 
    /**
     * @var null|string $id
     */
    protected null|string $id = null;
    
    /**
     * Returns the payload.
     *
     * @return array Must be serializable natively by json_encode().
     */
    abstract public function getPayload(): array;
    
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
        return static::class;
    }
    
    /**
     * Returns the job handler.
     *
     * @return callable
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function getCallableJobHandler(): callable
    {
        return [$this, 'handleJob'];
    }
}