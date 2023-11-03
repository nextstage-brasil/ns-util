<?php

namespace NsUtil\Services;

use Exception;
use NsUtil\Api;
use NsUtil\Exceptions\RedisConnectionException;
use NsUtil\Helper;
use NsUtil\Log;
use Predis\Client;

class Redis
{
    private static ?Client $client = null;
    public const FOREVER = (60 * 60 * 24 * 3650);

    private static function init()
    {
        try {
            if (null === self::$client) {
                self::$client = new Client([
                    'scheme' => 'tcp',
                    'host' => getenv('REDIS_HOST') ? getenv('REDIS_HOST') : 'host.docker.internal',
                    'port' => getenv('REDIS_PORT') ? getenv('REDIS_PORT') : 6379,
                    // 'prefix' => getenv('REDIS_PREFIX') ? getenv('REDIS_PREFIX') : ''
                ]);
            }

            self::$client->connect(); // Tenta conectar ao Redis
        } catch (Exception $e) {
            Log::logTxt(Helper::getTmpDir() . '/redis-error.log', [
                'error-message' => $e->getMessage(),
                'env-config' => [
                    'REDIS_HOST' => getenv('REDIS_HOST') ? getenv('REDIS_HOST') : 'default: host.docker.internal',
                    'REDIS_PORT' => getenv('REDIS_PORT') ? getenv('REDIS_PORT') : 'default: 6379',
                ]
            ]);
            throw new RedisConnectionException('Check rate limiter server configuration', 400);
        }
    }

    private static function prepareKey(&$key)
    {
        $ambiente = (string) getenv('AMBIENTE') ? getenv('AMBIENTE') : 'default';
        $prefix = getenv('REDIS_PREFIX') ? getenv('REDIS_PREFIX') : '';
        $key = (string) '_' . hash('crc32', "$key - $prefix - $ambiente");
    }

    /**
     * Set a cached value
     *
     * @param string $key Chave de recuperação
     * @param mixed $value Valor a ser cacheado
     * @param integer $timeInSeconds Valor em segundos da duração do cache
     * @return void
     */
    public static function set(string $key, $value, int $timeInSeconds)
    {
        self::init();
        self::prepareKey($key);

        if (is_callable($value)) {
            $ret = call_user_func($value);
            self::$client->set($key, $ret);
        } else {
            self::$client->set($key, $value);
        }
        self::$client->expire($key, $timeInSeconds);
    }

    public static function get($key, \Closure $fn = null, $timeInSeconds = null)
    {
        self::init();
        self::prepareKey($key);

        $ret = self::$client->get($key);
        if (strlen((string) $ret)  === 0) {
            if (is_callable($fn)) {
                $ret = call_user_func($fn);
                self::set($key, $ret, $timeInSeconds);
            }
        }
        return strlen((string) $ret) === 0 ? null : $ret;
    }

    public static function getHashedKey($content)
    {
        self::prepareKey($content);
        return $content;
    }

    public static function clearAll()
    {
        self::init();
        self::$client->flushall();
    }

    public static function clearKey($key): void
    {
        self::init();
        self::prepareKey($key);
        if (self::$client->exists($key)) {
            self::$client->del($key);
        }
    }

    /**
     * Set a cached value
     *
     * @param string $key Chave de recuperação
     * @param mixed $value Valor a ser cacheado
     * @param integer $timeInSeconds Valor em segundos da duração do cache
     * @return void
     */
    public static function update(string $key, $value)
    {
        $originalKey = $key;
        self::init();
        self::prepareKey($key);

        $ret = self::$client->get($key);
        if (strlen((string) $ret)  === 0) {
            throw new Exception("Redis: key '$originalKey' not found");
        }

        if (is_callable($value)) {
            $ret = call_user_func($value);
            self::$client->set($key, $ret);
        } else {
            self::$client->set($key, $value);
        }
    }

    public static function incr(string $key): void
    {
        $originalKey = $key;
        self::init();
        self::prepareKey($key);
        if (!self::$client->exists($key)) {
            throw new Exception("Redis: key '$originalKey' not found");
        }

        self::$client->incr($key);
    }
}
