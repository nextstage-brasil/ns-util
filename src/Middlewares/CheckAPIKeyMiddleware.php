<?php

namespace NsUtil\Middlewares;

use Exception;
use NsUtil\Api;
use NsUtil\Exceptions\RedisConnectionException;
use NsUtil\Exceptions\TooManyRequestException;
use NsUtil\Helper;
use NsUtil\Interface\MiddlewareInterface;
use NsUtil\Services\RateLimiter;

class CheckAPIKeyMiddleware implements MiddlewareInterface
{
    public ?string $apikey = null;

    // Rotas liberadas sem precisar de autenticação de usuário
    public array $freeRoutes = ['version', 'cpuAgent', 'healtcheck'];

    protected Api $api;

    public function __construct()
    {
    }

    public function handle(Api $api): bool
    {

        $this->api = $api;

        $this->setApiKey();;

        $resource = Helper::name2CamelCase($api->getConfigData()['rest']['resource']);

        if (in_array($resource, $this->freeRoutes)) {
            return true;
        }

        return $this->check();
    }
    public function setApiKey(): void
    {
        $headers = $this->api->getHeaders(true);
        $this->apikey = $headers[$this->apikey]
            ?? $headers['apikey']
            ?? $this->apikey;
    }

    public function check(): bool
    {
        throw new Exception('Check API not implemented in final class');
    }
}
