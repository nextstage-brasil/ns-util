<?php

namespace NsUtil\Integracao\Kissflow;

class CaseKF {

    private ClientKF $client;
    private string $caseId;
    private string $itemId;

    protected $case;

    public function __construct(ClientKF $client) {
        $this->client = $client;
    }

    public function getCaseId() {
        return $this->caseId;
    }

    public function setCaseId($caseId) {
        $this->caseId = $caseId;
        return $this;
    }

    public function getItemId() {
        return $this->itemId;
    }

    public function setItemId($itemId) {
        $this->itemId = $itemId;
        return $this;
    }



}
