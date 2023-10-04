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

namespace Tobento\Service\Queue\Test;

use Tobento\Service\Container\Container;
use Tobento\Service\Encryption\Crypto\KeyGenerator;
use Tobento\Service\Encryption\Crypto\EncrypterFactory;
use Tobento\Service\Encryption\KeyGeneratorInterface;
use Tobento\Service\Encryption\EncrypterFactoryInterface;
use Tobento\Service\Encryption\EncrypterInterface;
use Tobento\Service\Cache\Simple\Psr6Cache;
use Tobento\Service\Cache\ArrayCacheItemPool;
use Tobento\Service\Clock\SystemClock;
use Psr\SimpleCache\CacheInterface;

class Helper
{
    public static function bindEncrypterToContainer(Container $container): void
    {
        $container->set(EncrypterInterface::class, function() {
            return static::createEncrypter();
        });
    }
    
    public static function createEncrypter(): EncrypterInterface
    {
        $key = (new KeyGenerator())->generateKey();

        return (new EncrypterFactory())->createEncrypter(
            name: 'crypto',
            config: ['key' => $key],
        );
    }
    
    public static function createCache(): CacheInterface
    {
        return new Psr6Cache(
            pool: new ArrayCacheItemPool(
                clock: new SystemClock(),
            ),
            namespace: 'default',
            ttl: null,
        );
    }
}