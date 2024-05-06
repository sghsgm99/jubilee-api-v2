<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use App\Models\Services\SiteAdService;

/**
 * Class SiteRedirect
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $site_id
 * @property int $account_id
 * @property int $section
 * @property string $name
 * @property string $source
 * @property int $source_id
 * @property int $platform
 * @property int $disclosure
 * @property int $border
 * @property int $organic
 * @property string $min_slide
 * @property string $max_slide
 * @property string $tags
 *
 * Relationship
 * @property Site $site
 */
class SiteAd extends Model
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

    public function Service(): SiteAdService
    {
        return new SiteAdService($this);
    }

    /**
     * Relationship to the Site Model.
     *
     * @return BelongsTo
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
