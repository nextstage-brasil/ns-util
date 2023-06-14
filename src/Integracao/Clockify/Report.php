<?php

/**
 * Documentação: https://clockify.me/developers-api
 */

namespace NsUtil\Integracao\Clockify;

use DateTime;

class Report {

    private $client;

    public function __construct(ClockifyClient $client) {
        $this->client = $client;
        $this->client->configSet('url', 'https://reports.api.clockify.me/v1');
    }

    public function detailed(DateTime $dateStart, DateTime $dateEnd, string $taskSearch = null, array $filters = [], int $page = 1) {
        $filters['exportType'] = 'JSON';
        $filters['dateRangeStart'] = $dateStart->format('Y-m-d') . 'T00:00:00';
        $filters['dateRangeEnd'] = $dateEnd->format('Y-m-d') . 'T23:59:59';
        $filters['detailedFilter']['page'] = (int) $page;
        $filters['detailedFilter']['pageSize'] = 100;
        if (strlen($taskSearch) > 0) {
            $filters['description'] = $taskSearch;
        }
        return $this->client->call('reports/detailed', $filters, 'POST')['content'];
    }

}
