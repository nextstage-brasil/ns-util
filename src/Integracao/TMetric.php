<?php

namespace NsUtil\Integracao;

use Exception;
use NsUtil\Date;

/**
 * Class TMetric
 *
 * A controller class for interacting with the TMetric API.
 */
class TMetric extends \NsUtil\Controller\AbstractController
{
    /**
     * TMetric constructor.
     *
     * Initializes the TMetric controller by setting the API token and URL from the configuration.
     *
     * @param string  $token       The API token for TMetric.
     * @param integer $account_id  The account ID for TMetric.
     * @param string  $url         The base URL for TMetric API.
     */
    public function __construct(
        string $token,
        int $account_id,
        string $url = 'https://app.tmetric.com/api'
    ) {
        parent::__construct($url, $token, [
            'accountId' => $account_id
        ]);
    }

    /**
     * Loads time spent based on a specific date range and time entry filter.
     *
     * @param Date   $startDate         The start date of the time range.
     * @param Date   $endDate           The end date of the time range.
     * @param string $timeEntryFilter   The filter for time entry.
     *
     * @return array                    An array containing the loaded time spend data.
     *
     * @throws Exception                If an error occurs during the request or the response is invalid.
     */
    public function loadTimeSpend(Date $startDate, Date $endDate, string $timeEntryFilter)
    {
        $data = [
            'accountId' => $this->config->get('accountId'),
            'timeEntryFilter' => $timeEntryFilter,
            'useUtcTime' => 'false',
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ];
        $url = 'reports/summary/tasks';
        $headers = ['Authorization: Bearer ' . $this->config->get('token')];

        $out = parent::fetch(
            $url,
            $data,
            $headers,
            'GET',
            true,
            false
        );

        if (!is_array($out['content'])) {
            throw new Exception('TMetric returned an invalid response (TM-48)');
        }

        return $out['content'];
    }
}
