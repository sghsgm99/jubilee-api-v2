<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\RoleTypeEnum;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\Factories\FileServiceFactory;
use App\Models\Services\UserService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Class User
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $api_token
 * @property string $remember_token
 * @property int $is_owner
 * @property int $tester
 * @property RoleTypeEnum $role_id
 * @property string $role_setup
 * @property Carbon $email_verified_at
 *
 * Relationships
 * @property Setting $setting
 *
 * Accessor
 * @property-read string $full_name // getFullNameAttribute
 * @property-read boolean $is_active // getIsActiveAttribute
 *
 * Scopes
 * @method static Builder|User notUserId() // scopeNotUserId
 * @method static Builder|User isOwner() // scopeIsOwner
 * @method static Builder|User isNotOwner() // scopeIsNotOwner
 * @method static Builder|User search(string $search, string $status) // scopeSearch
 */
class User extends Authenticatable implements ImageableInterface
{
    use HasFactory, Notifiable, HasApiTokens;
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
    protected $hidden = [
        'password',
        'api_token',
        'remember_token',
    ];

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
        'email_verified_at' => 'datetime',
        'role_setup' => 'array',
        'is_owner' => 'boolean',
        'tester' => 'boolean',
        'role_id' => RoleTypeEnum::class
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function setting()
    {
        return $this->hasOne(Setting::class, 'user_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->attributes['first_name']} {$this->attributes['last_name']}";
    }

    public function getIsActiveAttribute()
    {
        return !$this->deleted_at;
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/users/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function Service(): UserService
    {
        return new UserService($this);
    }

    public function FileServiceFactory()
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO());
    }

    public function scopeIsOwner(Builder $query)
    {
        return $query->where('is_owner', '=', 1);
    }

    public function scopeIsNotOwner(Builder $query)
    {
        return $query->where('is_owner', '=', 0);
    }

    public function scopeNotUserId(Builder $query)
    {
        return $query->where('id', '!=', $this->id);
    }

    /**
     * @param Builder $query
     * @param string|null $search
     * @param string|null $status
     * @return Builder
     */
    public function scopeSearch(
        Builder $query,
        string $search = null,
        string $status = null,
        int $account_id = null,
        string $sort = null,
        string $sort_type = null
    ) {
        $status = strtolower(trim($status));

        if (!empty($search)) {
            // $query->where('first_name', 'like', '%'.$search.'%')
            //     ->orWhere('last_name', 'like', '%'.$search.'%')
            //     ->orWhere('email', 'like', '%'.$search.'%');

            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', '%' . $search . '%')
                    ->orWhere('last_name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if(!empty($account_id)){
            $query->where(function ($q) use ($account_id) {
                $q->where('account_id', $account_id);
            });
        }

        if ($status === null) {
            $query->withTrashed();
        }

        if ($status === 'inactive') {
            $query->onlyTrashed();
        }

        if ($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }

    public function roleSetup()
    {
        return $this->hasOne(RoleSetupTemplate::class, 'role_id', 'role_id');
    }
}
