<?php

namespace App\Models;

use App\Models\Enums\YahooReportTypeEnum;
use App\Models\Services\YahooReportService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class YahooReport
 *
 * Database Fields
 * @property int $id
 * @property Carbon $reported_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property YahooReportTypeEnum $type
 * @property array $data
 *
 * Scopes
 * @method static Builder|YahooReport type() // scopeType
 * @method static Builder|YahooReport source() // scopeSource
 * @method static Builder|YahooReport filter(?YahooReportTypeEnum $type, string $search = null, Carbon $from = null, Carbon $to = null, string $sort_type = 'desc') // scopeFilter
 */
class YahooReport extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'yahoo_reports';

    protected $appends = ['class_name'];

    protected $casts = [
        'type' => YahooReportTypeEnum::class,
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

    public function scopeType(Builder $builder)
    {
        return $builder->where('type', '=', YahooReportTypeEnum::TYPE);
    }

    public function scopeSource(Builder $builder)
    {
        return $builder->where('type', '=', YahooReportTypeEnum::SOURCE());
    }

    public function scopeFilter(
        Builder $query,
        YahooReportTypeEnum $type = null,
        string $search = null,
        Carbon $from = null,
        Carbon $to = null,
        string $sort_type = 'desc'
    )
    {
        if ($type->is(YahooReportTypeEnum::TYPE())) {
            $query->type();
        } elseif ($type->is(YahooReportTypeEnum::SOURCE())) {
            $query->source();
        }

        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('data->SOURCE_TAG', 'like', '%'.$search.'%')
                    ->orWhere('data->RN', 'like', '%'.$search.'%');
            });
        }

        if ($from && $to) {
            $query->whereBetween('reported_at', [$from->startOfDay(), $to->endOfDay()]);
        }

        return $query->orderBy('reported_at', $sort_type);
    }
}
