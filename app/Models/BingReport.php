<?php

namespace App\Models;

use App\Models\Enums\YahooReportTypeEnum;
use App\Models\Services\BingReportService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Enums\BingReportTypeEnum;

/**
 * Class BingReport
 *
 * Database Fields
 * @property int $id
 * @property int $job_id
 * @property int $account_id
 * @property string $job_id_string
 * @property string $name
 * @property string $status
 * @property string $download_url
 * @property BingReportTypeEnum $type
 * @property array $data
 * @property Carbon $reported_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationships
 * @property Channel $channel
 *
 * Scopes
 * @method static Builder|BingReport pc() // scopePC
 * @method static Builder|BingReport mobile() // scopeMobile
 * @method static Builder|BingReport tablet() // scopeTablet
 * @method static Builder|BingReport filter(BingReportTypeEnum $type = null, string $search = null, Carbon $from = null, Carbon $to = null, string $sort_type = 'desc') // scopeFilter
 */
class BingReport extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'bing_reports';

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

    public function Service(): BingReportService
    {
        return new BingReportService($this);
    }

    public function scopePC(Builder $builder)
    {
        return $builder->whereJsonContains('data->devicetype', BingReportTypeEnum::PC);
    }

    public function scopeMobile(Builder $builder)
    {
        return $builder->whereJsonContains('data->devicetype', BingReportTypeEnum::MOBILE);
    }

    public function scopeTablet(Builder $builder)
    {
        return $builder->whereJsonContains('data->devicetype', BingReportTypeEnum::TABLET);
    }

    public function scopeFilter(
        Builder $query,
        BingReportTypeEnum $type = null,
        string $search = null,
        Carbon $from = null,
        Carbon $to = null,
        string $sort_type = 'desc'
    )
    {
        if ($type->is(BingReportTypeEnum::PC())) {
            $query->pc();
        } elseif ($type->is(BingReportTypeEnum::MOBILE())) {
            $query->mobile();
        } elseif ($type->is(BingReportTypeEnum::TABLET())) {
            $query->tablet();
        }

        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('data->adunitname', 'like', '%'.$search.'%');
            });
        }

        if ($from && $to) {
            $query->whereBetween('reported_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        return $query->orderBy('reported_at', $sort_type);
    }
}
