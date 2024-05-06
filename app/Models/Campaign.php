<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\CampaignTypeEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\Factories\FileServiceFactory;
use App\Scopes\AccountScope;
use App\Services\FileService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\Services\CampaignService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Database Fields
 * @property int $id
 * @property string $title
 * @property string $description
 * @property array $channel_api_preferences
 * @property array $data_preferences
 * @property CampaignStatusEnum $status
 * @property CampaignTypeEnum $type
 * @property string $primary_text
 * @property string $headline
 * @property string $ad_description
 * @property string $display_link
 * @property FacebookCallToActionEnum $call_to_action
 * @property int $channel_id
 * @property int $site_id
 * @property int|null $article_id
 * @property int $user_id
 * @property integer $account_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationships
 * @property User $user
 * @property Article $article
 * @property Channel $channel
 * @property Site $site
 * @property CampaignTag $tags
 *
 * Scopes
 * @method static Builder|Campaign search(string $search = null, CampaignStatusEnum $status = null, array $tags = [], int $channel_id = null, string $sort = null, string $sort_type = null) // scopeSearch
 */
class Campaign extends Model implements ImageableInterface
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;
    use ImageableTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [];

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
        'deleted_at' => 'datetime',
        'status' => CampaignStatusEnum::class,
        'type' => CampaignTypeEnum::class,
        'platform' => ChannelPlatformEnum::class,
        'call_to_action' => FacebookCallToActionEnum::class,
        'channel_api_preferences' => 'array',
        'data_preferences' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/campaigns/{$this->id}/adset";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function Service(): CampaignService
    {
        return new CampaignService($this);
    }

    public function FileServiceFactory(string $dir = null): FileService
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO(), $dir);
    }

    /**
     * Relationship to the User Model.
     *
     * @return BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship to the Article Model.
     *
     * @return BelongsTo|Article
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Relationship to the Channel Model.
     *
     * @return BelongsTo|Channel
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    /**
     * Relationship to the Site Model.
     *
     * @return BelongsTo|Site
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    /**
     * Relationship to the Campaign Model.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(CampaignTag::class, 'campaign_tag_campaign', 'campaign_id', 'campaign_tag_id')->withTimestamps();
    }

    /**
     * @param Builder $query
     * @param string|null $search
     * @param CampaignStatusEnum|null $status
     * @param array $tags
     * @param int|null $channel_id
     * @param string|null $sort
     * @param string|null $sort_type
     * @return Builder
     */
    public function scopeSearch(
        Builder $query,
        string $search = null,
        CampaignStatusEnum $status = null,
        array $tags = [],
        int $channel_id = null,
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

        $tags = array_filter($tags);
        if (! empty($tags)) {
            $query->whereHas('tags', function ($query) use ($tags) {
               $query->whereIn('id', $tags);
            });
        }

        if ($channel_id){
            $query->where('channel_id', '=', $channel_id);
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
