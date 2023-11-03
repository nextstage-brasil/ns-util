# Classes para usos diversos

## Examples

#### API

- Atention: onError and onSuccess order on middlewares is important

```
<?php

use AppLibrary\App;
use JDrel\NsLibrary\Entities\Logs;
use NsUtil\Api;
use NsUtil\Helper;
use NsUtil\Services\RateLimiter;

if (is_dir(__DIR__ . '/../../_build/')) {
    error_reporting(E_ALL);
}

include __DIR__ . '/../../vendor/autoload.php';

App::init();
App::setDevelopeMode();
$namespace = Helper::getPsr4Name() . '\\Routers';

// Api
$api = new Api();
$headers = $api->getHeaders(true);
$resource = Helper::name2CamelCase($api->getConfigData()['rest']['resource']);
$apikey = $api->getHeaders(true)['apikey'] ?? $api->getConfigData()['ParamsRouter'][3] ?? '-1';

// Rotas liberadas sem precisar de autenticação de usuário
$freeRoutes = ['version', 'webhooks', 'cpuAgent'];

// log de uso
$hit = (new Logs([
    'appnameLog' => 'Api Consume',
    'textLog' => $apikey,
    'extrasLog' => [
        'route' => $resource,
        'headers' => $headers,
        'payload' => $api->getBody()
    ]
]))->save();

// Rate, Middlewares and Resolver
$dados = [];
$api
    ->onError(fn ($param1, $param2) => $hit->setExtrasLog(array_merge($hit->getExtrasLog('array'), ['response' => ['data' => $param1, 'code' => $param2]]))->save())
    ->onSuccess(fn ($param1, $param2) => $hit->setExtrasLog(array_merge($hit->getExtrasLog('array'), ['response' => ['code' => $param2]]))->save())
    ->rateLimit(3, 60)
    ->middleware('Invalid APIKEY', 403, fn () => in_array($resource, $freeRoutes) ? true : App::validaApiKey($apikey))
    ->rest($namespace);

```

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
