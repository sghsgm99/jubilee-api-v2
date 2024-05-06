<?php

namespace App\Exports;

use App\Models\Enums\RoleTypeEnum;
use App\Models\User;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    use Exportable;
    /**
    * @return \Illuminate\Support\Collection
    */
    public function __construct(array $ids)
    {
        if(empty($ids)){
            abort(400, 'User ids are required');
        }
        $this->ids = $ids;
    }

    public function collection()
    {
        return User::whereIn('id', $this->ids)->get();
    }

    public function headings(): array
    {
        return[
            'Name',
            'Email',
            'Role',
            'Owner',
            'Created At'
        ];
    }

    public function map($user): array
    {
        return [
            $user->first_name . " " . $user->last_name,
            $user->email,
            $user->role_id->getLabel(),
            $user->is_owner,
            $user->created_at->format('M d Y')
        ];
    }
}
