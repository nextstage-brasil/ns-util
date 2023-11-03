<?php

namespace NsUtil\Middlewares;

use NsUtil\Api;
use NsUtil\Exceptions\RedisConnectionException;
use NsUtil\Exceptions\TooManyRequestException;
use NsUtil\Helper;
use NsUtil\Interface\MiddlewareInterface;
use NsUtil\Services\RateLimiter;

class RateLimitMiddleware implements MiddlewareInterface
{
    public int $maxCallsLimit = 120;
    public int $secondsInterval = 60;
    public ?string $apikey = null;

    private Api $api;

    public function handle(Api $api): bool
    {
        $this->api = $api;

        $resource = Helper::name2CamelCase($this->api->getConfigData()['rest']['resource']);

        try {
            if (null !== $this->apikey) {
                RateLimiter::byKey($this->apikey, $this->maxCallsLimit, $this->secondsInterval, $resource);
            } else {
                RateLimiter::byIP($this->maxCallsLimit, $this->secondsInterval, $resource);
            }
        } catch (RedisConnectionException $exc) {
            $this->api->error($exc->getMessage(), $exc->getCode());
        } catch (TooManyRequestException $exc) {
            $this->api->error($exc->getMessage(), $exc->getCode());
        } catch (Exception $exc) {
            $this->api->error($exc->getMessage(), $exc->getCode());
        }

        return true;
    }

    public function setApiKey(?string $apikey = null): void
    {
        $this->apikey = $apikey
            ?? $this->api->getHeaders(true)['apikey']
            ?? $this->api->getConfigData()['ParamsRouter'][3]
            ?? '-1';
    }
}
