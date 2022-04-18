<?php

/**
 * Documentação: https://clockify.me/developers-api
 */

namespace NsUtil\Integracao\Clockify;

use Exception;
use NsUtil\Controller\AbstractController;
use NsUtil\Helper;

class ClockifyClient extends AbstractController {

    protected static $url = 'https://api.clockify.me/api/v1';
    protected $resource;

    public function __construct(string $apikey, string $workspaceName) {
        parent::__construct(self::$url, $apikey, []);
        $this->setWorkspaceIdByName($workspaceName);
    }

    protected function setWorkspaceIdByName(string $workspaceName): ClockifyClient {
        $headers = ["X-Api-Key:" . $this->config->get('token')];
        $list = parent::fetch('workspaces', [], $headers, 'GET');
        $item = Helper::arraySearchByKey($list['content'], 'name', $workspaceName);
        if (!isset($item['id'])) {
            throw new Exception('Workspace not found');
        }
        $this->config->set('workspaceId', (string) $item['id']);
        return $this;
    }

    public function call(string $resource, array $data = [], string $method = 'GET') {
        $resource = "workspaces/" . $this->config->get('workspaceId') . '/' . $resource;
        $headers = ["X-Api-Key:" . $this->config->get('token'), 'Content-Type:application/json'];
        return parent::fetch($resource, $data, $headers, $method);
    }

    public function configSet($key, $val) {
        $this->config->set($key, $val);
    }

    public function configGet($key) {
        return $this->config->get($key);
    }

}
