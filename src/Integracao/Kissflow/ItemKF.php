<?php

namespace NsUtil\Integracao\Kissflow;

use Exception;

class ItemKF {
    private ClientKF $client;
    private $resource;
    private $caseId;
    private $itemId;
    private $item;

    public function __construct(ClientKF $client, string $caseId, string $itemId) {
        $this->client = $client;
        $this->resource = "/${caseId}/${itemId}";
        $this->caseId = $caseId;
        $this->itemId = $itemId;
        $this->read();
    }

    private function checkIfIsRead() {
        if (!isset($this->item['_id'])) {
            throw new Exception('Item is not loaded');
        }
    }

    public function read(): self {
        if (strlen($this->itemId) > 0) {
            $resource = $this->resource;
            $data = [];
            $method = 'GET';
            $throwExceptionBasedStatus = true;
            $this->item = $this->client->call($resource, $data, $method, $throwExceptionBasedStatus);
        }
        return $this;
    }

    public function updateStatus(string $newStatusId): self {
        $this->checkIfIsRead();
        $resource = "$this->resource/" . $this->item['_status_id'] . '/move';
        $data = ['_status_id' => $newStatusId];
        $method = 'POST';
        $throwExceptionBasedStatus = true;
        $this->client->call($resource, $data, $method, $throwExceptionBasedStatus);
        $this->read();
        return $this;
    }
}
