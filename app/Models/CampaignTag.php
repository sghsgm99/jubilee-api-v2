<?php

namespace App\Models;

use App\Models\Services\CampaignTagService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class CampaignTag
 *
 * Database Fields
 * @property int $id
 * @property string $label
 * @property string $color
 * @property int $account_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationships
 * @property-read Campaign $campaigns
 * @property-read FacebookCampaign $facebookCampaigns
 *
 * Scopes
 * @method static Builder|CampaignTag whereLabel($value) // scopeWhereLabel
 */
class CampaignTag extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [];

    protected $appends = [];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): CampaignTagService
    {
        return new CampaignTagService($this);
    }

    /**
     * Relationship to the Campaign Model.
     */
    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_tag_campaign', 'campaign_tag_id', 'campaign_id')->withTimestamps();
    }

    /**
     * Relationship to the FacebookCampaign Model.
     */
    public function facebookCampaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_tag_facebook_campaign', 'campaign_tag_id', 'facebook_campaign_id')->withTimestamps();
    }

    public function scopeWhereLabel(Builder $query, $label): Builder
    {
        return $query->where('label', 'like', "%{$label}");
    }
}
