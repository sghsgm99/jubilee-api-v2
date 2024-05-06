<?php

namespace App\Models;

use App\Models\Services\SeoService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Seo class
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property int $seoable_id
 * @property string $seoable_type
 * @property string $title
 * @property string $keyword
 * @property string $tags
 *
 * Relationship
 * @property MorphTo $soeable
 */
class Seo extends Model
{
    use HasFactory;
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'keyword',
        'description',
        'tags',
        'account_id'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): SeoService
    {
        return new SeoService($this);
    }

    /**
     * @return MorphTo
     */
    public function soeable()
    {
        return $this->morphTo();
    }
}
