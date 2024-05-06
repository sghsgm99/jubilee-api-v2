<?php

namespace App\Models\Services;

use App\Models\Account;
use App\Models\ProgrammaticReport;
use Carbon\Carbon;

class ProgrammaticReportService extends ModelService
{
    private $programmaticReport;

    public function __construct(ProgrammaticReport $programmaticReport)
    {
        $this->programmaticReport = $programmaticReport;
        $this->model = $programmaticReport;
    }

    public static function create(
        Account $account,
        array $data = []
    )
    {
        $reported_at = ($data['campaign_date']) ? Carbon::parse($data['campaign_date']) : null;

        $programmatic_report = ProgrammaticReport::whereUpdatedDate($reported_at)
                ->whereDeviceCategory($data['device_category'])
                ->whereCampaign($data['utm_campaign'])
                ->whereDomain($data['domain'])
                ->first();

        if (!$programmatic_report) {
            $report = new ProgrammaticReport();
            $report->account_id = $account->id;
            $report->reported_at = $reported_at;
            $report->data = $data;

            $report->save();
        } else {
            $programmatic_report->reported_at = $reported_at;
            $programmatic_report->data = $data;

            $programmatic_report->save();
        }
    }

    public function update()
    {

    }
}
