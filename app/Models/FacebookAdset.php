<?php

namespace App\Models;

use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Services\FacebookAdsetModelService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\FacebookQueueableTrait;
use App\Traits\RuleAutomationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * FacebookAdset class
 *
 * Fields
 * @property int $id
 * @property int $campaign_id
 * @property string $fb_adset_id
 * @property string $title
 * @property array $data
 * @property CampaignInAppStatusEnum $status
 * @property FacebookCampaignStatusEnum $fb_status
 * @property int $user_id
 * @property int $account_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Carbon $errored_at
 * @property string $error_message
 *
 * Relationship
 * @property User $user
 * @property FacebookCampaign $campaign
 * @property FacebookAd $ads
 *
 * Scopes
 * @method static Builder|FacebookAdset whereFbStatus($value) // scopeWhereFbStatus
 */
class FacebookAdset extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;
    use FacebookQueueableTrait;
    use RuleAutomationTrait;

    protected $table = 'facebook_adsets';

    protected $fillable = [
        'fb_adset_id',
        'title'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'errored_at' => 'datetime',
        'data' => 'array',
        'status' => CampaignInAppStatusEnum::class,
        'fb_status' => FacebookCampaignStatusEnum::class,
    ];

    public function Service()
    {
        return new FacebookAdsetModelService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function campaign()
    {
        return $this->belongsTo(FacebookCampaign::class, 'campaign_id');
    }

    public function ads()
    {
        return $this->hasMany(FacebookAd::class, 'adset_id');
    }

    public function scopeWhereFbStatus(Builder $query, FacebookCampaignStatusEnum $statusEnum)
    {
        return $query->where('fb_status', '=', $statusEnum->value);
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        CampaignInAppStatusEnum $status = null,
        int $campaign_id = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($status->isNotUndefined()) {
            $query->where('status', '=', $status->value);
        }

        if ($campaign_id){
            $query->where('campaign_id', '=', $campaign_id);
        }


        if(Auth::user()->tester) {
            // filter based on test ad account
            $test_ad_account = config('facebook.test_ad_account');
            $query->whereHas('campaign.channel.channelFacebook', function($q) use($test_ad_account) {
                $q->where('ad_account', $test_ad_account);
            });
        } else {
            // filter based on parent business manager id
            $parent_bm_id = config('facebook.parent_bm.business_manager_id');
            $query->whereHas('campaign.channel.channelFacebook', function($q) use($parent_bm_id) {
                $q->where('parent_business_manager_id', $parent_bm_id);
                $q->orWhere('type', ChannelFacebookTypeEnum::STANDALONE);
            });
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
