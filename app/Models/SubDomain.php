<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enums\DNSStatusEnum;
use App\Models\Services\SubDomainService;
use App\Traits\BaseAccountModelTrait;

/**
 * Class SubDomain
 *
 * Database Fields
 * @property int $id
 * @property Carbon $reported_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $user_id
 * @property int $account_id
 * @property int $domain_id
 * @property string $name
 * @property DNSStatusEnum $status
 *
 * Relationship
 * @property User $user
 * @property Domain $domains
 *
 * Scopes
 * @method static Builder|SubDomain search(string $search = null, string $sort = null, string $sort_type = null) // scopeSearch
 * @method static Builder|SubDomain searchId(int $id, string $sort = null, string $sort_type = null) // scopeSearchId
 */
class SubDomain extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

    protected $table = 'subdomains';

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
        'status' => DNSStatusEnum::class
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): SubDomainService
    {
        return new SubDomainService($this);
    }

    /**
     * Relationship to the User Model.
     *
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function domains()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
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
                $q->where('name', 'like', '%'.$search.'%');
            });
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }

    public function scopeSearchId(
        Builder $query,
        int $id,
        string $sort = null,
        string $sort_type = null
    )
    {
        $query->where('domain_id', '=', $id);

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query->get();
    }
}
