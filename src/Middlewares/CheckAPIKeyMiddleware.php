<?php

namespace NsUtil\Middlewares;

use Exception;
use NsUtil\Api;
use NsUtil\Helper;
use NsUtil\Interfaces\MiddlewareInterface;

/**
 * CheckAPIKeyMiddleware validates the presence of API keys for secured routes and implements the MiddlewareInterface.
 */
class CheckAPIKeyMiddleware implements MiddlewareInterface
{
    /**
     * The API key used for authentication and access to secured routes.
     *
     * @var string|null
     */
    public ?string $apikey = null;

    /**
     * The list of routes that do not require user authentication for access.
     *
     * @var array
     */
    public array $freeRoutes = ['version', 'cpuAgent', 'healtcheck'];

    /**
     * The instance of the API handler.
     *
     * @var Api
     */
    protected Api $api;

    /**
     * Constructs the CheckAPIKeyMiddleware.
     */
    public function __construct()
    {
    }

    /**
     * Handles the API request by validating the API key for secured routes.
     *
     * @param Api $api - The API instance to handle the request.
     * @return bool - Returns true on successful API key validation for secure routes.
     */
    public function handle(Api $api): bool
    {
        $this->api = $api;

        $this->setApiKey();

        $resource = mb_strtolower(Helper::name2CamelCase($api->getConfigData()['rest']['resource']));
        $this->freeRoutes = array_map(fn($item) => mb_strtolower(Helper::name2CamelCase($item)), $this->freeRoutes);

        if (in_array($resource, $this->freeRoutes)) {
            return true;
        }

        return $this->check();
    }

    /**
     * Sets the API key for the middleware.
     */
    public function setApiKey(): void
    {
        $headers = $this->api->getHeaders(true);
        $this->apikey = $headers[mb_strtolower((string) $this->apikey ?? '-nsutil')]
            ?? $headers['apikey']
            ?? $this->apikey;
    }

    /**
     * Validates the API key for secure routes. This method should be implemented in the final class.
     *
     * @return bool - Returns true if the API key validation succeeds.
     * @throws Exception - Throws an exception since this method is not implemented in this abstract class.
     */
    public function check(): bool
    {
        throw new Exception('Check API not implemented in the final class');
    }
}
