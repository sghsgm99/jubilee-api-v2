<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
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
use App\Models\Services\KeywordSpinningService;

class KeywordSpinning extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

    protected $table = 'keywordspinning';

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
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): KeywordSpinningService
    {
        return new KeywordSpinningService($this);
    }

    public function scopeSearch(
        Builder $query,
        string $sort = null,
        string $sort_type = null,
        string $category
    )
    {
        $query->where(function ($q) use($category) {
            $q->where('category', '=', $category);
        });

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }

    public function scopeSearchEx(
        Builder $query,
        string $kw,
        string $url,
        string $category
    )
    {
        $query->where(function ($q) use($kw, $url, $category) {
            $q->where('keyword', '=', $kw)
                ->where('category', '=', $category)
                ->where('url', 'like', '%'.$url.'%');
        });

        return $query->count();
    }
}
