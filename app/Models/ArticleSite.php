<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\GenericStatusEnum;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\Factories\FileServiceFactory;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Services\ArticleService;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class ArticleSite
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $article_id
 * @property int $site_id
 * @property int $external_post_id
 * @property array $category_ids
 * @property array $tag_ids
 * @property GenericStatusEnum $status
 *
 * Scopes
 * @method static Builder|ArticleSite whereArticleId($value)
 * @method static Builder|ArticleSite whereSiteId($value)
 */
class ArticleSite extends Model
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
        'category_ids' => 'array',
        'tag_ids' => 'array',
        'menu_ids' => 'array',
    ];

    protected $table = 'article_site';

    public function scopeWhereArticleId(Builder $builder, int $article_id)
    {
        $builder->where('article_id', $article_id);
    }

    public function scopeWhereSiteId(Builder $builder, int $site_id)
    {
        $builder->where('site_id', $site_id);
    }
}
