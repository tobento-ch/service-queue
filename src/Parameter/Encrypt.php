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

use Tobento\Service\Queue\JobInterface;
use Tobento\Service\Queue\Job;
use Tobento\Service\Queue\QueueInterface;
use Tobento\Service\Queue\SyncQueue;
use Tobento\Service\Encryption\EncrypterInterface;
use JsonSerializable;

/**
 * Job encryption.
 */
class Encrypt extends Parameter implements JsonSerializable, Pushable, Poppable
{
    /**
     * Returns the priority.
     *
     * @return int
     */
    public function getPriority(): int
    {
        return -1000;
    }
    
    /**
     * Serializes the object to a value that can be serialized natively by json_encode().
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return ['encrypt' => true];
    }
    
    /**
     * Returns the pushing job handler.
     *
     * @return callable
     */
    public function getPushingJobHandler(): callable
    {
        return [$this, 'pushingJob'];
    }
    
    /**
     * Returns the popping job handler.
     *
     * @return callable
     */
    public function getPoppingJobHandler(): callable
    {
        return [$this, 'poppingJob'];
    }

    /**
     * Pushing job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param EncrypterInterface $encrypter
     * @return JobInterface
     */
    public function pushingJob(JobInterface $job, QueueInterface $queue, EncrypterInterface $encrypter): JobInterface
    {
        if ($queue instanceof SyncQueue) {
            return $job;
        }
        
        if ($job->parameters()->has(Data::class)) {
            $data = $job->parameters()->get(Data::class)?->data();
            $job->parameter(new Data(['encrypted' => $encrypter->encrypt($data)]));
        }
        
        return new Job(
            id: $job->getId(),
            name: $job->getName(),
            payload: ['encrypted' => $encrypter->encrypt($job->getPayload())],
            parameters: $job->parameters(),
        );
    }
    
    /**
     * Popping job.
     *
     * @param JobInterface $job
     * @param QueueInterface $queue
     * @param EncrypterInterface $encrypter
     * @return null|JobInterface
     */
    public function poppingJob(JobInterface $job, QueueInterface $queue, EncrypterInterface $encrypter): null|JobInterface
    {
        if ($queue instanceof SyncQueue) {
            return $job;
        }
        
        $payload = $job->getPayload();
        
        if (!isset($payload['encrypted'])) {
            return $job;
        }
        
        $data = $job->parameters()->get(Data::class)?->data();

        if (is_array($data) && isset($data['encrypted'])) {
            $job->parameter(new Data($encrypter->decrypt($data['encrypted'])));
        }
        
        return new Job(
            id: $job->getId(),
            name: $job->getName(),
            payload: $encrypter->decrypt($payload['encrypted']),
            parameters: $job->parameters(),
        );
    }
}