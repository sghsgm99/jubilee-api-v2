<?php

namespace App\Models;

use App\Models\Enums\BingReportTypeEnum;
use App\Models\Services\ProgrammaticReportService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ProgrammaticReport
 *
 * Database Fields
 * @property int $id
 * @property int $account_id
 * @property array $data
 * @property Carbon $reported_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Scopes
 * @method static Builder|ProgrammaticReport whereUpdateDate($value) // scopeWhereUpdatedDate
 * @method static Builder|ProgrammaticReport whereDeviceCategory($value) // scopeWhereDeviceCategory
 * @method static Builder|ProgrammaticReport whereCampaign($value) // scopeWhereCampaign
 * @method static Builder|ProgrammaticReport whereDomain($value) // scopeWhereDomain
 * @method static Builder|ProgrammaticReport whereCountry($value) // scopeWhereCountry
 * @method static Builder|ProgrammaticReport filter(Carbon $from = null, Carbon $to = null, string $sort_type = 'desc') // scopeFilter
 */
class ProgrammaticReport extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'programmatic_reports';

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

    public function Service(): ProgrammaticReportService
    {
        return new ProgrammaticReportService($this);
    }

    public function scopeWhereUpdatedDate(Builder $builder, Carbon $reported_at)
    {
        $builder->whereBetween('reported_at', [$reported_at->startOfDay(), $reported_at->endOfDay()]);
    }

    public function scopeWhereDeviceCategory(Builder $builder, string $device_category)
    {
        $builder->whereJsonContains('data->device_category', $device_category);
    }

    public function scopeWhereCampaign(Builder $builder, string $utm_campaign)
    {
        $builder->whereJsonContains('data->utm_campaign', $utm_campaign);
    }

    public function scopeWhereDomain(Builder $builder, string $domain)
    {
        $builder->whereJsonContains('data->domain', $domain);
    }

    public function scopeWhereCountry(Builder $builder, string $country)
    {
        $builder->whereJsonContains('data->country', $country);
    }

    public function scopeFilter(
        Builder $query,
        Carbon $from = null,
        Carbon $to = null,
        string $sort_type = 'desc'
    )
    {
        if ($from && $to) {
            $query->whereBetween('reported_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        return $query->orderBy('reported_at', $sort_type);
    }
}
