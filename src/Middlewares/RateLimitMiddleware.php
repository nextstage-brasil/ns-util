<?php

namespace NsUtil\Middlewares;

use Exception;
use NsUtil\Api;
use NsUtil\Exceptions\RedisConnectionException;
use NsUtil\Exceptions\TooManyRequestException;
use NsUtil\Helper;
use NsUtil\Interface\MiddlewareInterface;
use NsUtil\Services\RateLimiter;

/**
 * RateLimitMiddleware provides a rate-limiting mechanism for API requests and implements MiddlewareInterface for request handling.
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    /**
     * The maximum limit of API calls allowed within a specific time interval.
     *
     * @var int
     */
    public int $maxCallsLimit = 60;

    /**
     * The time interval (in seconds) within which the maximum calls limit is considered.
     *
     * @var int
     */
    public int $secondsInterval = RateLimiter::PER_MINUTE;

    /**
     * The API key for identifying and limiting requests (defaulted to 'not-defined').
     *
     * @var string|null
     */
    public ?string $apikey = 'not-defined';

    /**
     * An associative array to configure rate limits by different API routes.
     * For example: 'healthcheck' => [200, RateLimiter::PER_MINUTE]
     *
     * @var array
     */
    public array $configByRoute = [];

    /**
     * A flag indicating whether the API key is set for rate limiting.
     *
     * @var bool
     */
    public bool $setApiKey = false;

    /**
     * The instance of the API handler.
     *
     * @var Api
     */
    private Api $api;

    /**
     * Constructs the RateLimitMiddleware.
     */
    public function __construct()
    {
    }

    /**
     * Handles the API request by applying rate limiting rules and verifying API keys (if configured).
     *
     * @param Api $api - The API instance to handle the request.
     * @return bool - Returns true on successful request handling.
     */
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

        $this->maxCallsLimit = $this->configByRoute[$resource][0] ?? $this->maxCallsLimit;
        $this->secondsInterval = $this->configByRoute[$resource][1] ?? $this->secondsInterval;

        $this->setApiKey();

        $this->check();
        try {
            if ($this->setApiKey && null !== $this->apikey) {
                RateLimiter::byKey($this->apikey, $this->maxCallsLimit, $this->secondsInterval);
            } else {
                RateLimiter::byIP($this->maxCallsLimit, $this->secondsInterval);
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

    /**
     * Sets the API key for rate limiting.
     */
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

    /**
     * Checks if the request passes the rate-limiting criteria.
     *
     * @return bool - Returns true if the request passes the rate-limiting check.
     */
    public function check(): bool
    {
        return true;
    }
}
