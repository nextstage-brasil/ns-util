<?php

namespace NsUtil\Services;

use NsUtil\Api;
use NsUtil\Date;
use NsUtil\Exceptions\TooManyRequestException;
use NsUtil\Helper;
use NsUtil\Services\Redis;

use function NsUtil\dd;
use function NsUtil\now;

class RateLimiter
{
    private static ?Redis $redis = null;

    public const PER_DAY = -1;
    public const PER_HOUR = 60 * 60;
    public const PER_MINUTE = 60;
    public const PER_SECOND = 1;


    private function __construct()
    {
    }

    private static function init()
    {
        if (null === self::$redis) {
            self::$redis = new Redis();
        }
    }

    private static function getIpAddress(): string
    {
        return Helper::getIP();
    }

    public static function byIP(int $maxCallsLimit = 120, int $secondsInterval = self::PER_MINUTE, ?string $route = null): void
    {
        self::handle($maxCallsLimit, $secondsInterval, null, $route);
    }

    public static function byKey(string $key, int $maxCallsLimit = 100, int $secondsInterval = self::PER_HOUR, ?string $route = null): void
    {
        self::handle($maxCallsLimit, $secondsInterval, $key, $route);
    }



    private static function handle(
        int $maxCallsLimit = 3,
        int $secondsInterval = 10,
        ?string $appkey = null,
        ?string $route = null
    ): void {
        self::init();

        $rateKey = $appkey ?? self::getIpAddress() . '-' . (string) $route;

        if (null === self::$redis::get($rateKey)) {

            // per_day
            if ($secondsInterval === -1) {
                $mezzanotte = new Date(date('Y-m-d H:i:s', mktime(23, 59, 59, date('m'), date('d'), date('Y'))));
                $secondsInterval = $mezzanotte->timestamp() - now()->timestamp();
            }

            self::$redis::set($rateKey, 0, $secondsInterval);
        }

        $totalUserCalls = ((int) self::$redis::get($rateKey)) + 1;
        if ($totalUserCalls > $maxCallsLimit) {
            throw new TooManyRequestException('Limite exceed. (' . $rateKey . ')');
        }

        self::$redis::incr($rateKey);
    }

    public static function clear(?string $appkey = null): void
    {
        self::init();
        $rateKey = $appkey ?? self::getIpAddress();
        self::$redis::clearKey($rateKey);
    }
}
