<?php

namespace NsUtil\Controller;

class AbstractController {

    protected $config;

    public function __construct($url, $token, $config = []) {
        $config['token'] = $token;
        $config['url'] = $url;
        $this->config = new \NsUtil\Config($config);
    }

    /**
     * Fetch calls
     * @param string $resource
     * @param array $data
     */
    protected function fetch(string $resource, array $data = [], array $headers = [], string $method = 'GET'): array {
        $url = $this->config->get('url')
                . '/'
                . $resource
        ;
        $ret = \NsUtil\Helper::curlCall($url, $data, $method, $headers);
        if ($ret->status !== 200) {
            throw new \Exception('Chamada ao recurso ' . $resource . ' com status ' . $ret->status);
        }
        $ret->content = json_decode($ret->content);

        return json_decode(json_encode($ret), true);
    }
    
}
