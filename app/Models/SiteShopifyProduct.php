<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use App\Models\Services\SiteShopifyProductService;

class SiteShopifyProduct extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

    protected $table = 'site_shopify_products';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'data' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): SiteShopifyProductService
    {
        return new SiteShopifyProductService($this);
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function scopeWhereSiteId(Builder $query, $id)
    {
        return $query->where('site_id', '=', $id);
    }
}
