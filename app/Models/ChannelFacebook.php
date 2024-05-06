<?php

namespace App\Models;

use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\FacebookTimezoneEnum;
use App\Models\Enums\FacebookVerticalEnum;
use App\Models\Services\ChannelFacebookService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ChannelFacebook class
 *
 * Fields
 * @property int $id
 * @property string $name
 * @property int $page_id
 * @property string|null $parent_business_manager_id
 * @property ChannelFacebookTypeEnum $type
 * @property FacebookTimezoneEnum $timezone
 * @property FacebookVerticalEnum $vertical
 *
 * Relationships
 * @property Channel $channel
 *
 * Scopes
 * @method static Builder|ChannelFacebook whereParentBMNotNull() // scopeWhereParentBMNotNull
 */
class ChannelFacebook extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'channel_facebook';

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'type' => ChannelFacebookTypeEnum::class,
        'timezone' => FacebookTimezoneEnum::class,
        'vertical' => FacebookVerticalEnum::class,
        'page_permitted_tasks' => 'array',
    ];

    public function Service(): ChannelFacebookService
    {
        return new ChannelFacebookService($this);
    }

    public function scopeWhereParentBMNotNull(Builder $builder)
    {
        return $builder->whereNotNull('parent_business_manager_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
