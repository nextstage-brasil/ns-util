# Classes para usos diversos

## Examples

#### Rate Limit

```
// to clear:
// php nsutil rate:clear {appkey_or_IP}

try {
    // rate 120 calls by ip per minute
    RateLimiter::byIP(120, RateLimiter::PER_MINUTE);

    // rate 100 calls per apikey per day
    RateLimiter::byKey('my-app-key-1', 100, RateLimiter::PER_DAY);

    // Exception on error to connect on redis
} catch (RedisConnectionException $exc) {
    http_response_code($exc->getCode() ?? 400);
    echo "REDIS-ERROR: " . $exc->getMessage() . PHP_EOL;
    // Too many requests (429)
} catch (TooManyRequestException $exc) {
    http_response_code($exc->getCode());
    echo $exc->getMessage() . PHP_EOL;
    // Others exceptions
} catch (Exception $exc) {
    http_response_code($exc->getCode() ?? 400);
    echo "ERROR: " . $exc->getMessage() . PHP_EOL;
}
```
