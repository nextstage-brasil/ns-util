<?php

namespace NsUtil\Integracao\Gitlab;

use Exception;
use NsUtil\Exceptions\ModelNotFoundException;
use NsUtil\Integracao\Gitlab;

use function NsUtil\dd;

class Issue
{

    private $issue = [];
    private $client;
    private $iid;


    public function __construct($idProject, $issue_iid, Gitlab $client, array $issueData = [])
    {
        $this->client = $client;
        $this->client->setIdProject($idProject);
        $this->iid = $issue_iid;

        $issueData['projectId'] = $idProject;
        $issueData['issue_id'] = $issue_iid;
        $issueData['iid'] = $issue_iid;
        $this->setIssue($issueData);
    }

    public function load()
    {
        try {
            $data = $this->client->read(
                "projects/" . $this->client->getIdProject() . "/issues",
                $this->iid
            );

            if ([] === $data || null === $data) {
                throw new Exception('Issue not found: ' . $this->iid . ' on project ' . $this->client->getIdProject());
            }

            $this->setIssue($data, false);
        } catch (\Exception $exc) {
            throw new ModelNotFoundException($exc->getMessage());
        }

        return $this;
    }

    public function getData()
    {
        return $this->issue;
    }

    public function update(array $data)
    {
        $this->client->issueEdit($this->iid, $data);
        return $this->load();
    }

    /**
     *
     * @param array $data
     * @param boolean $merge
     * @return self
     */
    public function setIssue(array $data, bool $merge = true): self
    {
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
    public function updateLabels(array $labels, bool $merge = false): self
    {
        $this->client->issueEdit(
            $this->issue['iid'],
            ['labels' => implode(',', $labels)]
        );
        return $this->load();
    }

    /**
     *
     * @param string $comments
     * @return self
     */
    public function addComments(string $comments): self
    {
        $this->client->addComments(
            $this->issue['iid'],
            $comments
        );
        return $this;
    }

    public function setEstimate(int $estimate)
    {
        $this->client->setEstimate($this->issue['iid'], $estimate);
        return $this->load();
    }
}
