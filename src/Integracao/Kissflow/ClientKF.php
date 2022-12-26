<?php

namespace NsUtil\Integracao\Kissflow;

use Closure;
use NsUtil\Controller\AbstractController;

class ClientKF extends AbstractController {
    private $ClosureOnError;

    //https://jdrel.kissflow.com/case/2/Aczd1Tpt_SQW/Time_de_produto_Conversor/TPCO-0010/update?

    public function __construct(
        string $apikey,
        string $url,
        string $accountId,
        string $accessKey,
        string $secret,
        Closure $onError = null
    ) {

        $this->ClosureOnError = $onError;
        parent::__construct(
            $url . "/case/2/${accountId}",
            $apikey,
            [
                'accountId' => $accountId,
                'accessKey' => $accessKey,
                'secret' => $secret
            ]
        );
    }

    public function call(string $resource, array $data = [], string $method = 'POST', $throwExceptionBasedStatus=false): array {
        $headers =  [
            'accept:application/json',
            'content-type:application/json',
            'X-Access-Key-Id:' . $this->configGet('accessKey'),
            'X-Access-Key-Secret:' . $this->configGet('secret'),
        ];
        try {
            return parent::fetch($resource, $data, $headers, $method, $throwExceptionBasedStatus);
        } catch (\Exception $ex) {
            if (is_callable($this->ClosureOnError)) {
                call_user_func($this->ClosureOnError, $ex->getMessage());
            }
            return ['error' => $ex->getMessage()];
        }
    }

    public function configSet($key, $val) {
        $this->config->set($key, $val);
    }

    public function configGet($key) {
        return $this->config->get($key);
    }
}