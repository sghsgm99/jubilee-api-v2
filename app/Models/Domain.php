<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Enums\DNSStatusEnum;
use App\Models\Services\DomainService;
use App\Traits\BaseAccountModelTrait;
use App\Models\SubDomain;

class Domain extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;

    protected $table = 'domains';

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

    public function Service(): DomainService
    {
        return new DomainService($this);
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

    public function subdomains()
    {
        return $this->hasMany(SubDomain::class, 'domain_id', 'id');
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
                $q->where('server', 'like', '%'.$search.'%')
                ->orWhere('domain', 'like', '%'.$search.'%');
            });
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
