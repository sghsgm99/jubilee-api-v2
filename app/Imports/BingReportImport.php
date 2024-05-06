<?php

namespace App\Imports;

use App\Models\Account;
use App\Models\Services\BingReportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class BingReportImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    use Importable;

    /**
     * @param Account $account
     */
    private $account;

    /**
     * @param object $response
     */
    private $response;

    public function __construct(Account $account, object $response)
    {
        $this->account = $account;
        $this->response = $response;
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            $data = $row->toArray();

            if ($this->validate($data)->fails()) {
                continue;
            }

            BingReportService::create(
                $this->account,
                $this->response->message->jobId,
                $this->response->message->reportName,
                $this->response->message->status,
                $this->response->message->downloadUrl,
                Carbon::parse($data['date']),
                $data
            );
        }
    }

    public function chunkSize(): int
    {
        return 10;
    }

    public function headingRow(): int
    {
        return 5;
    }

    private function validate(array $row)
    {
        return Validator::make($row, [
            'date' => 'required|date'
        ]);
    }
}
