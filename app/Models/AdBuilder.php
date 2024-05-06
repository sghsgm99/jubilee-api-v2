<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Services\AdBuilderService;

/**
 * Class AdBuilder
 *
 * Database Fields
 * @property int $id
 * @property string $name
 * @property string $url
 * @property string|null $gjs_components
 * @property string|null $gjs_style
 * @property string|null $gjs_html
 * @property string|null $gjs_css
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Scopes
 * @method static Builder|AdBuilder search(string $search = null, string $sort = null, string $sort_type = null) // scopeSearch
 */
class AdBuilder extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'adbuilders';

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
        'deleted_at' => 'datetime'
    ];

    public function Service(): AdBuilderService
    {
        return new AdBuilderService($this);
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('name', 'like', '%'.$search.'%')
                ->orWhere('url', 'like', '%'.$search.'%');
            });
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
