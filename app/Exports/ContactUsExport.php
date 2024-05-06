<?php

namespace App\Exports;

use App\Models\ContactUs;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactUsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;

    public function __construct(array $ids)
    {
        if(empty($ids)){
            abort(400, 'Contact us ids are required');
        }
        $this->ids = $ids;
    }

    public function collection()
    {
        return ContactUs::whereIn('id', $this->ids)->get();
    }

    public function headings(): array
    {
        return [
            'Name',
            'Email',
            'Company',
            'Message',
            'Submitted At'
        ];
    }

    public function map($contact_us): array
    {
        return [
            $contact_us->name,
            $contact_us->email,
            $contact_us->company,
            $contact_us->message,
            $contact_us->created_at->format('M d Y')
        ];
    }
}
