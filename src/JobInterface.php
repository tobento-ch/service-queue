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
 * JobInterface
 */
interface JobInterface
{
    /**
     * Returns the job id.
     *
     * @return string
     */
    public function getId(): string;
    
    /**
     * Returns the job name.
     *
     * @return string
     */
    public function getName(): string;
    
    /**
     * Returns the payload.
     *
     * @return array Must be serializable natively by json_encode().
     */
    public function getPayload(): array;

    /**
     * Returns the parameters.
     *
     * @return ParametersInterface
     */
    public function parameters(): ParametersInterface;
    
    /**
     * Add a parameter.
     *
     * @param ParameterInterface $parameter
     * @return static $this
     */
    public function parameter(ParameterInterface $parameter): static;
}