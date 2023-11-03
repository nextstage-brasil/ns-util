<?php

namespace NsUtil\Middlewares;

use Exception;
use NsUtil\Api;
use NsUtil\Exceptions\RedisConnectionException;
use NsUtil\Exceptions\TooManyRequestException;
use NsUtil\Helper;
use NsUtil\Interface\MiddlewareInterface;
use NsUtil\Services\RateLimiter;

class RateLimitMiddleware implements MiddlewareInterface
{
    public int $maxCallsLimit = 60;
    public int $secondsInterval = RateLimiter::PER_MINUTE;
    public ?string $apikey = 'not-defined';

    public array $configByRoute = [];

    public bool $setApiKey = false;

    private Api $api;

    public function __construct()
    {
    }

    public function handle(Api $api): bool
    {
        $this->api = $api;

        $resource = mb_strtolower(Helper::name2CamelCase($api->getConfigData()['rest']['resource']));

        $this->configByRoute = array_map(
            fn ($item) => mb_strtolower(Helper::name2CamelCase($item)),
            array_merge($this->configByRoute, [
                'healthcheck' => [200, RateLimiter::PER_MINUTE]
            ])
        );

        $maxCallsLimit = $this->configByRoute[$resource][0] ?? $this->maxCallsLimit;
        $secondsInterval = $this->configByRoute[$resource][1] ?? $this->secondsInterval;

        $this->setApiKey();

        $this->check();

        try {
            if ($this->setApiKey && null !== $this->apikey) {
                RateLimiter::byKey($this->apikey, $maxCallsLimit, $secondsInterval);
            } else {
                RateLimiter::byIP($maxCallsLimit, $secondsInterval);
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

    public function setApiKey(): void
    {
        $headers = $this->api->getHeaders(true);
        $this->apikey = $this->apikey === 'not-defined'
            ? ($headers[$this->apikey]
                ?? $headers['apikey']
                ?? $this->apikey
                ?? '-1'
            )
            : $this->apikey;
    }

    public function check(): bool
    {
        return true;
    }
}
