<?php

/**
 * Documentação: https://clockify.me/developers-api
 */

namespace NsUtil\Integracao\Clockify;

use Exception;
use NsUtil\Helper;

class Project implements InterfaceClockify {

    private $client;

    public function __construct(ClockifyClient $client) {
        $this->client = $client;
    }

    public function getSettedId() {
        return $this->client->configGet('projectId');
    }

    public function setByName(string $projectName): Project {
        $list = $this->client->call('projects');
        $item = Helper::arraySearchByKey($list['content'], 'name', $projectName);
        if (!isset($item['id'])) {
            throw new Exception('Project not found');
        }
        $this->client->configSet('projectId', (string) $item['id']);
        return $this;
    }

    public function setById(string $projectID): Project {
        $item = $this->client->call('projects/' . $projectID)['content'];
        if (!isset($item['id'])) {
            throw new Exception('Project not found');
        }
        $this->client->configSet('projectId', (string) $item['id']);
        return $this;
    }

    public function create($name): Project {
        $item = $this->client->call('projects', ['name' => $name], 'POST')['content'];
        if (!isset($item['id'])) {
            throw new Exception('Error on create project');
        }
        $this->client->configSet('projectId', (string) $item['id']);
        return $this;
    }

}
