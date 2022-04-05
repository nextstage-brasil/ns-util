<?php

namespace NsUtil\Integracao;

class Clockfy extends \NsUtil\Controller\AbstractController {

    private static $url = 'https://api.clockify.me/api/v1';
    private $resource;

    public function __construct(string $apikey, string $workspaceName) {
        parent::__construct(self::$url, $apikey, []);
        $this->setWorkspaceIdByName($workspaceName);
    }

    private function setWorkspaceIdByName(string $workspaceName): Clockfy {
        $headers = ["X-Api-Key:" . $this->config->get('token')];
        $list = parent::fetch('workspaces', [], $headers, 'GET');
        $item = \NsUtil\Helper::arraySearchByKey($list['content'], 'name', $workspaceName);
        if (!isset($item['id'])) {
            throw new \Exception('Workspace not found');
        }
        $this->config->set('workspaceId', (string) $item['id']);
        return $this;
    }

    public function setProjectIdByName($projectName): Clockfy {
        $list = $this->call('projects');
        $item = \NsUtil\Helper::arraySearchByKey($list['content'], 'name', $projectName);
        if (!isset($item['id'])) {
            throw new \Exception('Project not found');
        }
        $this->config->set('projectId', (string) $item['id']);
        return $this;
    }

    private function call(string $resource, array $data = [], string $method = 'GET') {
        $resource = "workspaces/" . $this->config->get('workspaceId') . '/' . $resource;
        $headers = ["X-Api-Key:" . $this->config->get('token')];
        return parent::fetch($resource, $data, $headers, $method);
    }

    public function tasks() {
        return $this->call('projects/' . $this->config->get('projectId') . '/tasks')['content'];
    }

    public function entryes() {
        
    }

    public function pr() {
        var_export($this->config->getAll());
    }

}
