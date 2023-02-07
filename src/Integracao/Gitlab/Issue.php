<?php

namespace NsUtil\Integracao\Gitlab;

use NsUtil\Integracao\Gitlab;

class Issue {

    private $issue = [];
    private $client;


    public function __construct($idProject, $issue_iid, Gitlab $client, array $issueData = []) {
        $this->client = $client;
        $this->client->setIdProject($idProject);
        $issueData['projectId'] - $idProject;
        $issueData['issue_id'] = $issue_iid;
        $this->setIssue($issueData);
    }

    /**
     *
     * @param array $data
     * @param boolean $merge
     * @return self
     */
    public function setIssue(array $data, bool $merge = true): self {
        $this->issue = $merge
            ? array_merge($this->issue, $data)
            : $data;
        return $this;
    }

    /**
     *
     * @param array $labels
     * @param boolean $merge
     * @return self
     */
    public function updateLabels(array $labels, bool $merge = false): self {
        $this->client->issueEdit(
            $this->issue['issue_id'],
            ['labels' => implode(',', $labels)]
        );
        return  $this;
    }

    /**
     *
     * @param string $comments
     * @return self
     */
    public function addComments(string $comments): self {
        $this->client->addComments(
            $this->issue['issue_id'],
            $comments
        );
        return $this;
    }
}
