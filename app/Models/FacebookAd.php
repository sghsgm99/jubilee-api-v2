<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\FacebookAdModelService;
use App\Models\Services\Factories\FileServiceFactory;
use App\Services\FileService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use App\Traits\FacebookQueueableTrait;
use App\Traits\RuleAutomationTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FacebookAd class
 *
 * Fields
 * @property int $id
 * @property int $adset_id
 * @property string|null $fb_ad_id
 * @property int|null $article_id
 * @property int $site_id
 * @property string|null $title
 * @property string|null $primary_text
 * @property string|null $headline
 * @property string|null $description
 * @property string|null $display_link
 * @property FacebookCallToActionEnum|null $call_to_action
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
 * Accessor
 * @property-read Channel|null $campaign_channel // getCampaignChannelAttribute
 * @property-read string $type // getTypeAttribute
 * @property-read string $title_format // getTitleFormatAttribute
 * @property-read string $link_format // getLinkFormatAttribute
 * @property-read Image|null $featured_image // getFeaturedImageAttribute
 *
 * Relationship
 * @property User $user
 * @property FacebookAdset $adset
 * @property Site $site
 * @property Article $article
 *
 * Scopes
 * @method static Builder|FacebookAd whereAdsetId(int $adset_id) // scopeWhereAdsetId
 * @method static Builder|FacebookAd whereFbStatus($value) // scopeWhereFbStatus
 * @method static Builder|FacebookAd search(string $search = null, string $status = null) // scopeSearch
 */
class FacebookAd extends Model implements ImageableInterface
{
    use BaseAccountModelTrait;
    use SoftDeletes;
    use ImageableTrait;
    use FacebookQueueableTrait;
    use RuleAutomationTrait;

    protected $table = 'facebook_ads';

    /**
     * The attributes that are mass assignable.
     * Use for duplicate Ad feature
     *
     * @var string[]
     */
    protected $fillable = [
        'fb_ad_id',
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
        'call_to_action' => FacebookCallToActionEnum::class,
        'status' => CampaignInAppStatusEnum::class,
        'fb_status' => FacebookCampaignStatusEnum::class,
    ];

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/facebook-ads/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function getCampaignChannelAttribute(): ?Channel
    {
        return $this->adset->campaign->channel ?? null;
    }

    public function getTitleFormatAttribute(): ?string
    {
        if ($this->article) {
            return $this->title ?? $this->article->title;
        }

        return $this->title;
    }

    public function getLinkFormatAttribute(): string
    {
        if ($this->article) {
            return "{$this->site->url}/article/{$this->article->slug}";
        }

        if ($this->site) {
            return $this->site->url;
        }

        return $this->display_link ?? '';
    }

    public function getTypeAttribute(): string
    {
        if ($this->article_id && $this->article) {
            return 'Article Driven';
        }

        return 'Standalone';
    }

    public function getFeaturedImageAttribute(): ?Image
    {
        if (! $this->article) {
            return $this->featureImage ?? null;
        }

        if ($this->article->featureImage) {
            return $this->article->featureImage;
        }

        return $this->article->image ?? null;
    }

    public function Service(): FacebookAdModelService
    {
        return new FacebookAdModelService($this);
    }

    public function FileServiceFactory(string $dir = null): FileService
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO(), $dir);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function adset()
    {
        return $this->belongsTo(FacebookAdset::class, 'adset_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    public function scopeWhereAdsetId(Builder $query, int $adset_id)
    {
        return $query->where('adset_id', '=', $adset_id);
    }

    public function scopeWhereFbStatus(Builder $query, FacebookCampaignStatusEnum $statusEnum)
    {
        return $query->where('fb_status', '=', $statusEnum->value);
    }

    public function scopeSearch(Builder $query, string $search = null, CampaignInAppStatusEnum $status = null)
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($status->isNotUndefined()) {
            $query->where('status', '=', $status->value);
        }

        return $query;
    }
}
