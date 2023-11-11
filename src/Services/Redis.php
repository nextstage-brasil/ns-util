<?php

namespace NsUtil\Services;

use Exception;
use NsUtil\Api;
use NsUtil\Exceptions\RedisConnectionException;
use NsUtil\Helper;
use NsUtil\Log;
use Predis\Client;

use NsUtil\json_decode;

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

    private static function prepareKey($key): string
    {
        $ambiente = (string) getenv('AMBIENTE') ? getenv('AMBIENTE') : 'default';
        $prefix = getenv('REDIS_PREFIX') ? getenv('REDIS_PREFIX') : '';
        return '_' . md5("$key - $prefix - $ambiente");
    }

    /**
     * Set a cached value
     *
     * @param string $key Chave de recuperação
     * @param mixed $value Valor a ser cacheado
     * @param integer $timeInSeconds Valor em segundos da duração do cache
     * @return void
     */
    public static function set(string $key, $value, int $timeInSeconds = self::FOREVER)
    {
        self::init();
        $key = self::prepareKey($key);

        if (is_callable($value)) {
            $ret = call_user_func($value);
            self::$client->set($key, $ret);
        } else {
            self::$client->set($key, $value);
        }

        self::$client->expire($key, $timeInSeconds);
    }

    public static function get($key, $value = null, int $timeInSeconds = self::FOREVER)
    {

        self::init();

        $ret = self::$client->get(self::prepareKey($key));

        if (strlen((string) $ret) === 0 && $value !== null) {
            self::set($key, $value, $timeInSeconds);
            $ret = self::$client->get(self::prepareKey($key));
        }
        return strlen((string) $ret) === 0 ? null : $ret;
    }


    public static function clearAll()
    {
        self::init();
        self::$client->flushall();
    }

    public static function clearKey($key): void
    {
        self::init();
        $key = self::prepareKey($key);
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
        $key = self::prepareKey($key);

        $ret = self::$client->get($key);
        if (strlen((string) $ret) === 0) {
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
        $key = self::prepareKey($key);
        if (!self::$client->exists($key)) {
            throw new Exception("Redis: key '$originalKey' not found");
        }

        self::$client->incr($key);
    }

    public static function cacheArray($key, $value = null, int $timeInSeconds = self::FOREVER)
    {
        $content = self::get($key, function () use ($value) {
            return (string) json_encode(is_callable($value) ? call_user_func($value) : $value);
        }, $timeInSeconds);

        return json_decode($content, true);
    }

    public static function cacheJson($key, $value = null, int $timeInSeconds = self::FOREVER)
    {
        $ret = self::cacheArray($key, $value, $timeInSeconds);

        return json_decode($ret);
    }

    public function cache($key, $value = null, int $timeInSeconds = self::FOREVER)
    {
        return self::get($key, function () use ($value) {
            return is_callable($value) ? call_user_func($value) : $value;
        }, $timeInSeconds);
    }
}
