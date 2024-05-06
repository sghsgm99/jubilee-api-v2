<?php

namespace App\Models\Services;

use App\Exports\YahooDDCReportsExport;
use App\Exports\YahooAmgReportsExport;
use App\Models\Account;
use App\Models\Enums\YahooDDCReportTypeEnum;
use App\Models\Enums\YahooReportTypeEnum;
use App\Models\YahooReport;
use App\Models\YahooDDCReport;
use Carbon\Carbon;

class YahooReportService extends ModelService
{
    /**
     * @var YahooReport
     */
    private $yahooReport;

    public function __construct(YahooReport $yahooReport)
    {
        $this->yahooReport = $yahooReport;
        $this->model = $yahooReport;
    }

    public static function create(
        Account $account,
        YahooReportTypeEnum $type,
        array $data = []
    ): YahooReport
    {
        $report = new YahooReport();
        $report->account_id = $account->id;
        $report->type = $type;
        $report->reported_at = ($data['DATA_HOUR']) ? Carbon::parse($data['DATA_HOUR']) : null;
        $report->data = $data;

        $report->save();
        return $report;
    }

    public static function create_ddc(
        Account $account,
        array $data = []
    )
    {
        $reported_at = ($data['update_date']) ? Carbon::parse($data['update_date']) : null;

        $yahoo_ddc_report = YahooDDCReport::whereUpdatedDate($reported_at)
                ->whereType($data['device_type'])
                ->whereCampaign($data['campaign'])
                ->whereDomain($data['domain'])
                ->first();

        if (!$yahoo_ddc_report) {
            $report = new YahooDDCReport();
            $report->account_id = $account->id;
            $report->type = $data['device_type'];
            $report->reported_at = $reported_at;
            $report->data = $data;

            $report->save();
        } else {
            $yahoo_ddc_report->reported_at = $reported_at;
            $yahoo_ddc_report->data = $data;

            $yahoo_ddc_report->save();
        }
    }

    public function update()
    {

    }

    public static function exportDCCReport(
        string $from,
        string $to,
        YahooDDCReportTypeEnum $type
    )
    {
        $date = date('Y-m-d');
        return (new YahooDDCReportsExport($from, $to, $type))->download($date . 'yahoo_ddc_report.csv');
    }

    public static function exportAmgReport(
        string $from,
        string $to,
        YahooReportTypeEnum $type
    )
    {
        $date = date('Y-m-d');
        return (new YahooAmgReportsExport($from, $to, $type))->download($date . '_yahoo_amg_report.csv');
    }
}
