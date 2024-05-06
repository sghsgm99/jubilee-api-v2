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
use App\Models\Enums\BlackListStatusEnum;
use App\Models\Enums\BlackListTypeEnum;
use App\Models\Services\BlackListService;

/**
 * Class BlackList
 *
 * Database Fields
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property string $name
 * @property string $domain
 * @property BlackListStatusEnum $status
 * @property BlackListTypeEnum $type
 * @property string|null $subdomain
 * @property string|null $gjs_css
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationships
 * @property User $user
 *
 * Scopes
 * @method static Builder|BlackList search(string $search = null, string $sort = null, string $sort_type = null) // scopeSearch
 * @method static Builder|BlackList searchEx(string $url) // scopeSearchEx
 */
class BlackList extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'blacklists';

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
        'type' => BlackListTypeEnum::class,
        'status' => BlackListStatusEnum::class
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): BlackListService
    {
        return new BlackListService($this);
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
                ->orWhere('domain', 'like', '%'.$search.'%')
                ->orWhere('subdomain', 'like', '%'.$search.'%');
            });
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }

    public function scopeSearchEx(
        Builder $query,
        string $url
    )
    {
        $v = explode('.', $url);
        $subdomain = count($v) > 2 ? $v[0] : '';

        $query->where(function ($q) use($url, $subdomain) {
            $q->where('status', '=', 1)
            ->where('domain', 'like', '%'.$url.'%')
            ->orWhere('subdomain', '=', $subdomain);
        });

        return $query->count();
    }
}
