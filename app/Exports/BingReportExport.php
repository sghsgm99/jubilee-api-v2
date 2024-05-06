<?php

namespace App\Exports;

use App\Models\BingReport;
use App\Models\Enums\BingReportTypeEnum;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BingReportExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(
        string $from,
        string $to,
        BingReportTypeEnum $type
    )
    {
        $this->from = $from;
        $this->to = $to;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type->isUndefined()){
            return BingReport::whereBetween('reported_at', [$this->from, $this->to])
                            ->get();
        }

        return BingReport::whereBetween('reported_at', [$this->from, $this->to])
                        ->where('data->devicetype', $this->type)
                        ->get();
    }

    public function headings(): array
    {
        return [
            'REPORTED DATE',
            'AD UNIT ID',
            'AD UNIT NAME',
            'CLICKS',
            'DEVICE TYPE',
            'ESTIMATED REVENUE',
            'IMPRESSIONS',
            'QUERIES',
            'TYPE TAG',
            'MARKET'
        ];
    }

    public function map($report): array
    {
        return [
            $report->reported_at->format('M d Y'),
            $report->data['adunitid'],
            $report->data['adunitname'],
            $report->data['clicks'],
            $report->data['devicetype'],
            $report->data['estimatedrevenue'],
            $report->data['impressions'],
            $report->data['queries'],
            $report->data['typetag'],
            $report->data['market']
        ];
    }
}
