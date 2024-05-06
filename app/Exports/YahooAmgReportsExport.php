<?php

namespace App\Exports;

use App\Models\Enums\YahooReportTypeEnum;
use App\Models\YahooReport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class YahooAmgReportsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(
        string $from,
        string $to,
        YahooReportTypeEnum $type)
    {
        $this->from = $from;
        $this->to = $to;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type->isUndefined()){
            return YahooReport::whereBetween('reported_at', [$this->from, $this->to])
                            ->get();
        }

        return YahooReport::whereBetween('reported_at', [$this->from, $this->to])
                        ->where('type', $this->type)
                        ->get();
    }

    public function headings(): array
    {
        return [
            'REPORTED DATE',
            'RN',
            'CTR',
            'PPC',
            'RPS',
            'MARKET',
            'PRODUCT',
            'COVERAGE',
            'SEARCHES',
            'TQ SCORE',
            'DATA HOUR',
            'SOURCE TAG',
            'BIDDED CLICKS',
            'BIDDED RESULTS',
            'BIDDED SEARCHES',
            'ESTIMATED GROSS REVENUE'
        ];
    }

    public function map($report): array
    {
        return [
            $report->reported_at->format('M d Y'),
            $report->data['RN'],
            $report->data['CTR'],
            $report->data['PPC'],
            $report->data['RPS'] ?? $report->data['RPS'] ?? null,
            $report->data['MARKET'] ?? $report->data['MARKET'] ?? null,
            $report->data['PRODUCT'],
            $report->data['COVERAGE'],
            $report->data['SEARCHES'],
            $report->data['TQ_SCORE'],
            $report->data['DATA_HOUR'],
            $report->data['SOURCE_TAG'],
            $report->data['BIDDED_CLICKS'],
            $report->data['BIDDED_RESULTS'],
            $report->data['BIDDED_SEARCHES'],
            $report->data['ESTIMATED_GROSS_REVENUE']
        ];
    }
}
