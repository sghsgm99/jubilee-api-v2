<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\AccountService;
use App\Models\Services\Factories\FileServiceFactory;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Account
 *
 * Database Fields
 *
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property string $company_name
 * @property string $facebook_app_id
 * @property string $facebook_app_secret
 * @property string $facebook_business_manager_id
 * @property string $facebook_line_of_credit_id
 * @property string $facebook_primary_page_id
 * @property string $facebook_access_token
 * @property string $report_token
 * @property string $view_id
 * @property string $analytic_file
 * @property string $analytic_script
 *
 * Accessor
 * @property-read bool $has_analytics_setup // getHasAnalyticsSetupAttribute
 *
 * Relationships
 * @property User $users
 */
class Account extends Model implements ImageableInterface
{
    use HasFactory;
    use BaseAccountModelTrait;
    use ImageableTrait;

    protected $appends = ['class_name'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/accounts/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function getAnalyticFileAttribute()
    {
        $file = storage_path("app{$this->attributes['analytic_file']}");

        if (! $this->attributes['analytic_file']) {
            return null;
        }

        if (! file_exists($file)) {
            return null;
        }

        return $file;
    }

    public function getHasAnalyticsSetupAttribute()
    {
        if (! $this->attributes['view_id'] && ! $this->getAnalyticFileAttribute() && ! $this->attributes['analytic_script']) {
            return false;
        }

        return true;
    }

    public function Service(): AccountService
    {
        return new AccountService($this);
    }

    public function FileServiceFactory(string $dir = null, StorageDiskEnum $storageDiskEnum = null)
    {
        if ($storageDiskEnum === null) {
            $storageDiskEnum = StorageDiskEnum::PUBLIC_DO();
        }

        return FileServiceFactory::resolve($this, $storageDiskEnum, $dir);
    }

    /**
     * Relationship to the User Model.
     *
     * @return HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'account_id', 'id');
    }

    /**
     * Scope Search Account
     */
    public function scopeSearch(
        Builder $query,
        string $search = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('company_name', 'like', '%'.$search.'%');
            });
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
