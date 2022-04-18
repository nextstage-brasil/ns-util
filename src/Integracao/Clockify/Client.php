<?php

/**
 * Documentação: https://clockify.me/developers-api
 */

namespace NsUtil\Integracao\Clockify;

use Exception;
use NsUtil\Helper;

class Client implements InterfaceClockify {

    private $client;

    public function __construct(ClockifyClient $client) {
        $this->client = $client;
    }

    public function getSettedId() {
        return $this->client->configGet('clientId');
    }

    public function setByName(string $name): Client {
        $list = $this->client->call('clients');
        $item = Helper::arraySearchByKey($list['content'], 'name', $name);
        if (!isset($item['id'])) {
            throw new \Exception('Client not found');
        }
        $this->client->configSet('clientId', (string) $item['id']);
        return $this;
    }

    public function setById(string $id): Client {
        $item = $this->client->call('projects/' . $id)['content'];
        if (!isset($item['id'])) {
            throw new Exception('Client not found');
        }
        $this->client->configSet('clientId', (string) $item['id']);
        return $this;
    }

    public function create($name): Client {
        $item = $this->client->call('clients', ['name' => $name], 'POST')['content'];
        if (!isset($item['id'])) {
            throw new Exception('Error on create new client');
        }
        $this->client->configSet('clientId', (string) $item['id']);
        return $this;
    }

}
