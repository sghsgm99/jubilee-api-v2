<?php

namespace App\Models;

use App\Models\Services\ClickscoReportService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClickscoReport extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'clicksco_reports';

    protected $appends = ['class_name'];

    protected $casts = [
        'data' => 'array',
        'reported_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): ClickscoReportService
    {
        return new ClickscoReportService($this);
    }

    public function scopeFilter(
        Builder $query,
        string $search = null,
        Carbon $from = null,
        Carbon $to = null,
        string $sort_type = 'desc'
    )
    {
        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('data->adunitname', 'like', '%'.$search.'%');
            });
        }

        if ($from && $to) {
            $query->whereBetween('data->ryt_date', [$from->startOfDay(), $to->endOfDay()]);
        }

        return $query->orderBy('reported_at', $sort_type);
    }
}
