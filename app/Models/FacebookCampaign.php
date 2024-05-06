<?php

namespace App\Models;

use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Services\FacebookCampaignModelService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\FacebookQueueableTrait;
use App\Traits\RuleAutomationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * FacebookCampaign class
 *
 * Fields
 * @property int $id
 * @property string $fb_campaign_id
 * @property string $title
 * @property string $description
 * @property int $channel_id
 * @property string $ad_account_id
 * @property FacebookCampaignObjectiveEnum $objective
 * @property CampaignInAppStatusEnum $status
 * @property FacebookCampaignStatusEnum $fb_status
 * @property int $facebook_rule_automation_id
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
 * @property FacebookAdset $adsets
 * @property Channel $channel
 * @property FacebookRuleAutomation $fbRuleAutomation
 * @property CampaignTag $tags
 *
 * Scopes
 * @method Builder|FacebookCampaign whereFbStatus($value) // scopeWhereFbStatus
 */
class FacebookCampaign extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;
    use FacebookQueueableTrait;
    use RuleAutomationTrait;

    protected $table = 'facebook_campaigns';

    protected $fillable = [
        'fb_campaign_id',
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
        'objective' => FacebookCampaignObjectiveEnum::class,
        'status' => CampaignInAppStatusEnum::class,
        'fb_status' => FacebookCampaignStatusEnum::class,
    ];

    public function Service()
    {
        return new FacebookCampaignModelService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adsets()
    {
        return $this->hasMany(FacebookAdset::class, 'campaign_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Relationship to the CampaignTag Model.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CampaignTag::class, 'campaign_tag_facebook_campaign', 'facebook_campaign_id', 'campaign_tag_id')->withTimestamps();
    }

    public function fbRuleAutomation()
    {
        return $this->belongsTo(FacebookRuleAutomation::class, 'facebook_rule_automation_id', 'id');
    }

    public function scopeWhereFbStatus(Builder $query, FacebookCampaignStatusEnum $statusEnum)
    {
        return $query->where('fb_status', '=', $statusEnum->value);
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        CampaignInAppStatusEnum $status = null,
        array $tags = [],
        int $channel_id = null,
        string $sort = null,
        string $sort_type = null,
        string $ad_account_id = null
    )
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($status->isNotUndefined()) {
            $query->where('status', '=', $status->value);
        }

        $tags = array_filter($tags);
        if (! empty($tags)) {
            $query->whereHas('tags', function ($query) use ($tags) {
               $query->whereIn('id', $tags);
            });
        }

        if ($channel_id){
            $query->where('channel_id', '=', $channel_id);
        }

        if ($ad_account_id){
            $query->where('ad_account_id', '=', $ad_account_id);
        }


        if(Auth::user()->tester) {
            // filter based on test ad account
            $test_ad_account = config('facebook.test_ad_account');
            $query->whereHas('channel.channelFacebook', function($q) use($test_ad_account) {
                $q->where('ad_account', $test_ad_account);
            });
        } else {
            // filter based on parent business manager id
            $parent_bm_id = config('facebook.parent_bm.business_manager_id');
            $query->whereHas('channel.channelFacebook', function($q) use($parent_bm_id) {
                $q->where('parent_business_manager_id', $parent_bm_id);
                $q->orWhere('type', ChannelFacebookTypeEnum::STANDALONE);
            });
        }

        if($sort) {
            switch ($sort) {
                case 'channel':
                    $query->orderBy(
                        Channel::select('title')
                        ->whereColumn('id', 'channel_id')
                        ->orderBy('title')
                    , $sort_type);
                    break;

                default:
                    $query->orderBy($sort, $sort_type);
                    break;
            }

        }

        return $query;
    }
}
