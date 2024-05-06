<?php

namespace App\Models;

use App\Models\Services\GoogleReportService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Enums\ReportPlatformEnum;

class GoogleReport extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'google_reports';

    protected $appends = ['class_name'];

    protected $casts = [
        'data' => 'array',
        'reported_at' => 'datetime',
        'platform' => ReportPlatformEnum::class,
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): GoogleReportService
    {
        return new GoogleReportService($this);
    }

    public function scopeWhereUpdatedDate(Builder $builder, Carbon $reported_at)
    {
        $builder->whereBetween('reported_at', [$reported_at->startOfDay(), $reported_at->endOfDay()]);
    }

    public function scopeWhereClientId(Builder $builder, string $client_id)
    {
        $builder->where('client_id', $client_id);
    }

    public function scopeWherePlatform(Builder $builder, string $platform)
    {
        $builder->where('platform', $platform);
    }

    public function scopeWhereChannel(Builder $builder, string $channel)
    {
        $builder->where('channel', $channel);
    }

    public function scopeDesktop(Builder $builder)
    {
        return $builder->where('platform', '=', ReportPlatformEnum::DESKTOP);
    }

    public function scopeMobile(Builder $builder)
    {
        return $builder->where('platform', '=', ReportPlatformEnum::MOBILE);
    }

    public function scopeTablet(Builder $builder)
    {
        return $builder->where('platform', '=', ReportPlatformEnum::TABLET);
    }

    public function scopeFilter(
        Builder $query,
        ReportPlatformEnum $platform = null,
        string $search = null,
        Carbon $from = null,
        Carbon $to = null,
        string $sort = null,
        string $sort_type = 'desc'
    )
    {
        if ($platform->is(ReportPlatformEnum::DESKTOP())) {
            $query->desktop();
        } elseif ($platform->is(ReportPlatformEnum::MOBILE())) {
            $query->mobile();
        } elseif ($platform->is(ReportPlatformEnum::TABLET())) {
            $query->tablet();
        }

        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('client_id', 'like', '%'.$search.'%')
                    ->orWhere('channel', 'like', '%'.$search.'%');
            });
        }

        if ($from && $to) {
            $query->whereBetween('reported_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        if ($sort) {
            $query->orderBy('data->'.$sort, $sort_type);
        }

        return $query->orderBy('created_at', $sort_type);
    }
}
