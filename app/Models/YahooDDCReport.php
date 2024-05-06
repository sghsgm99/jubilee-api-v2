<?php

namespace App\Models;

use App\Models\Enums\YahooReportTypeEnum;
use App\Models\Enums\YahooDDCReportTypeEnum;
use App\Models\Services\YahooReportService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class YahooDDCReport
 *
 * Database Fields
 * @property int $id
 * @property Carbon $reported_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property string $type
 * @property array $data
 *
 * Scopes
 * @method static Builder|YahooDDCReport whereUpdatedDate(Carbon $reported_at) // scopeWhereUpdatedDate
 * @method static Builder|YahooDDCReport whereType(string $value) // scopeWhereType
 * @method static Builder|YahooDDCReport whereCampaign(string $value) // scopeWhereCampaign
 * @method static Builder|YahooDDCReport whereDomain(string $value) // scopeWhereDomain
 * @method static Builder|YahooDDCReport desktop() // scopeDesktop
 * @method static Builder|YahooDDCReport mobile() // scopeMobile
 * @method static Builder|YahooDDCReport tablet() // scopeTablet
 * @method static Builder|YahooDDCReport filter(?YahooDDCReportTypeEnum $type, string $search = null, Carbon $from = null, Carbon $to = null, string $sort_type = 'desc') // scopeFilter
 */
class YahooDDCReport extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'yahoo_ddc_reports';

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

    public function Service(): YahooReportService
    {
        return new YahooReportService($this);
    }

    public function scopeWhereUpdatedDate(Builder $builder, Carbon $reported_at)
    {
        $builder->whereBetween('reported_at', [$reported_at->startOfDay(), $reported_at->endOfDay()]);
    }

    public function scopeWhereType(Builder $builder, string $type)
    {
        $builder->where('type', $type);
    }

    public function scopeWhereCampaign(Builder $builder, string $campaign)
    {
        $builder->whereJsonContains('data->campaign', $campaign);
    }

    public function scopeWhereDomain(Builder $builder, string $domain)
    {
        $builder->whereJsonContains('data->domain', $domain);
    }

    public function scopeDesktop(Builder $builder)
    {
        return $builder->where('type', YahooDDCReportTypeEnum::DESKTOP);
    }

    public function scopeMobile(Builder $builder)
    {
        return $builder->where('type', YahooDDCReportTypeEnum::SMART_PHONES);
    }

    public function scopeTablet(Builder $builder)
    {
        return $builder->where('type', YahooDDCReportTypeEnum::TABLET);
    }

    public function scopeFilter(
        Builder $query,
        YahooDDCReportTypeEnum $type = null,
        string $search = null,
        Carbon $from = null,
        Carbon $to = null,
        string $sort_type = 'desc'
    )
    {
        if ($type->is(YahooDDCReportTypeEnum::DESKTOP())) {
            $query->desktop();
        } elseif ($type->is(YahooDDCReportTypeEnum::SMART_PHONES())) {
            $query->mobile();
        } elseif ($type->is(YahooDDCReportTypeEnum::TABLET())) {
            $query->tablet();
        }

        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('data->domain', 'like', '%'.$search.'%')
                    ->orWhere('data->campaign', 'like', '%'.$search.'%');
            });
        }

        if ($from && $to) {
            $query->whereBetween('reported_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        return $query->orderBy('reported_at', $sort_type);
    }
}
