<?php

namespace App\Models\Services;

use App\Models\Account;
use App\Models\ClickscoReport;
use Carbon\Carbon;

class ClickscoReportService extends ModelService
{
    private $clickscoReport;

    public function __construct(ClickscoReport $clickscoReport)
    {
        $this->clickscoReport = $clickscoReport;
        $this->model = $clickscoReport;
    }

    public static function create(
        Account $account,
        string $name,
        Carbon $reported_at,
        array $raw_data = []
    ): ClickscoReport
    {
        $report = new ClickscoReport();
        $report->account_id = $account->id;
        $report->name = $name;
        $report->reported_at = $reported_at;
        $report->data = $raw_data;

        $report->save();
        return $report;
    }
}
