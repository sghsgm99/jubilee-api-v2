<?php

namespace App\Exports;

use App\Models\Enums\ReportPlatformEnum;
use App\Models\GoogleReport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class GoogleReportsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */

    public function __construct(
        string $from,
        string $to,
        ReportPlatformEnum $platform
    )
    {
        $this->from = $from;
        $this->to = $to;
        $this->platform = $platform;
    }

    public function collection()
    {
        if($this->platform->isUndefined()){
            return GoogleReport::whereBetween('updated_at', [$this->from, $this->to])
                            ->get();
        }

        return GoogleReport::whereBetween('updated_at', [$this->from, $this->to])
                        ->where('data->platform', $this->platform)
                        ->get();
    }

    public function headings(): array
    {
        return [
            'UPDATED DATE',
            'CLIENT ID',
            'PLATFORM',
            'CHANNEL',
            'CLICKS',
            'CLICKS SPAM',
            'COVERAGE',
            'CPC',
            'NET REVENUE',
            'CTR',
            'IMPRESSIONS',
            'IMPRESSIONS SPAM',
            'MATCHED QUERIES',
            'QUERIES',
            'QUERIES SPAM',
            'RPM'
        ];
    }

    public function map($report): array
    {
        return [
            $report->updated_at->format('M d Y'),
            $report->client_id,
            $report->data['platform'],
            $report->channel,
            $report->data['clicks'],
            $report->data['clicks_spam'],
            $report->data['coverage'],
            $report->data['cpc'],
            $report->data['net_revenue'],
            $report->data['ctr'],
            $report->data['impressions'],
            $report->data['impressions_spam'],
            $report->data['matched_query'],
            $report->data['queries'],
            $report->data['queries_spam'],
            $report->data['rpm']
        ];
    }
}
