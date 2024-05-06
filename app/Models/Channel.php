<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\Factories\FileServiceFactory;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use App\Models\Enums\FacebookAudienceTypeEnum;
use App\Models\Services\ChannelService;
use Illuminate\Support\Facades\Auth;

/**
 * Class Channel
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $user_id
 * @property int $account_id
 * @property string $title
 * @property string $api_key
 * @property string $api_secret_key;
 * @property string $ad_account_key;
 * @property array $api_callback
 * @property array $api_permissions
 * @property string $access_token;
 * @property string $content
 * @property ChannelPlatformEnum $platform
 * @property ChannelStatusEnum $status
 *
 * Relationships
 * @property Account $account
 * @property User $user
 * @property Image $image
 * @property Image $images
 * @property ChannelFacebook $channelFacebook
 *
 * Accessor
 * @property-read string $full_name // getFullNameAttribute
 * @property-read boolean $is_active // getIsActiveAttribute
 *
 * Scopes
 * @method static Builder|User search(string $search, ChannelPlatformEnum $platform, ChannelStatusEnum $status) // scopeSearch
 */
class Channel extends Model implements ImageableInterface
{
    use HasFactory;
    use BaseAccountModelTrait;
    use ImageableTrait;
    use SoftDeletes;

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
        'platform' => ChannelPlatformEnum::Class,
        'status' => ChannelStatusEnum::Class,
        'api_callback' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/channels/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function Service(): ChannelService
    {
        return new ChannelService($this);
    }

    public function FileServiceFactory()
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO());
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

    public function channelFacebook()
    {
        return $this->hasOne(ChannelFacebook::class);
    }

    /**
     * Relationship to the Analytic Model.
     *
     * @return
     * hasOne|Analytic
     */

    public function analytics()
    {
        return $this->hasOne(Analytic::class, 'channel_id');
    }

    /**
     * Relationship to the Campaign Model.
     *
     * @return
     * hasMany|Campaign
     */

    public function facebookCampaigns()
    {
        return $this->hasMany(FacebookCampaign::class, 'channel_id');
    }

    public function facebook_campaigns()
    {
        return $this->hasMany(Campaign::class, 'channel_id');
    }

    public function facebook_audiences()
    {
        return $this->hasMany(FacebookAudience::class, 'channel_id');
    }

    public function facebook_custom_audiences()
    {
        return $this->hasMany(FacebookAudience::class, 'channel_id')->where('audience_type', "!=", FacebookAudienceTypeEnum::LOOKALIKE);
    }

    /**
     * @param Builder $query
     * @param string|null $search
     * @param ChannelPlatformEnum|null $platform
     * @param ChannelStatusEnum|null $status
     * @return Builder
     */
    public function scopeSearch(
        Builder $query,
        string $search = null,
        ChannelPlatformEnum $platform = null,
        ChannelStatusEnum $status = null,
        int $owner = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if(Auth::user()->tester) {
            $test_ad_account = config('facebook.test_ad_account');
            $query->whereHas('channelFacebook', function($q) use($test_ad_account) {
                $q->where('ad_account', $test_ad_account);
            });
        } else {
            $parent_bm_id = config('facebook.parent_bm.business_manager_id');
            $query->whereHas('channelFacebook', function($q) use($parent_bm_id) {
                $q->where('parent_business_manager_id', $parent_bm_id);
                $q->orWhere('type', ChannelFacebookTypeEnum::STANDALONE()->value);
            });
        }

        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('api_key', 'like', '%'.$search.'%');
            });
        }

        if ($platform->isNotUndefined()) {
            $query->where('platform', '=', $platform->value);
        }

        if ($status->isNotUndefined()) {
            $query->where('status', '=', $status->value);
        }

        if ($owner){
            $query->where('user_id', '=', $owner);
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
