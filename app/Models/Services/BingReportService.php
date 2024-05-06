<?php

namespace App\Models\Services;

use App\Exports\BingReportExport;
use App\Models\Account;
use App\Models\BingReport;
use App\Models\Enums\BingReportTypeEnum;
use Carbon\Carbon;

class BingReportService extends ModelService
{
    /**
     * @var BingReport
     */
    private $bingReport;

    public function __construct(BingReport $bingReport)
    {
        $this->bingReport = $bingReport;
        $this->model = $bingReport;
    }

    public static function create(
        Account $account,
        int $job_id,
        string $name,
        string $status,
        string $download_url,
        Carbon $reported_at,
        array $data = []
    ): BingReport
    {
        $report = new BingReport();
        $report->account_id = $account->id;
        $report->job_id = $job_id;
        $report->job_id_string = (string) $job_id;
        $report->name = $name;
        $report->status = $status;
        $report->download_url = $download_url;
        $report->reported_at = $reported_at;
        $report->data = $data;

        $report->save();
        return $report;
    }

    public static function export(
        string $from,
        string $to,
        BingReportTypeEnum $type
    )
    {
        $date = date('Y-m-d');
        return (new BingReportExport($from, $to, $type))->download($date . '_bing_report.csv');
    }
}
