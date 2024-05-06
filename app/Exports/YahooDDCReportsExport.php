<?php

namespace App\Exports;

use App\Models\Enums\YahooDDCReportTypeEnum;
use App\Models\YahooDDCReport;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class YahooDDCReportsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(
        string $from,
        string $to,
        YahooDDCReportTypeEnum $type
    )
    {
        $this->from = $from;
        $this->to = $to;
        $this->type = $type;
    }

    public function collection()
    {
        if($this->type->isUndefined()){
            return YahooDDCReport::whereBetween('reported_at', [$this->from, $this->to])
                            ->get();
        }

        return YahooDDCReport::whereBetween('reported_at', [$this->from, $this->to])
                        ->where('type', $this->type)
                        ->get();
    }

    public function headings(): array
    {
        return [
            'REPORTED DATE',
            'DOMAIN',
            'COUNTRY CODE',
            'DEVICE TYPE',
            'CAMPAIGN',
            'SEARCHES',
            'CLICKS',
            'REVENUE',
            'TQ',
            'COVERAGE'
        ];
    }

    public function map($report): array
    {
        return [
            $report->reported_at->format('M d Y'),
            $report->data['domain'],
            $report->data['country_code'],
            $report->data['device_type'],
            $report->data['campaign'],
            $report->data['searches'],
            $report->data['clicks'],
            $report->data['revenue'],
            $report->data['tq'],
            $report->data['coverage'],
        ];
    }
}
