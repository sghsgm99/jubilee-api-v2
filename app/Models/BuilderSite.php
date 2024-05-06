<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\BuilderSiteService;
use App\Models\Services\Factories\FileServiceFactory;
use App\Scopes\AccountScope;
use App\Services\FileService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class BuilderSite
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property string $name
 * @property string $domain
 * @property string $seo
 * @property string $api_builder_key
 * @property string $host
 * @property string $ssh_username
 * @property string $ssh_password
 * @property string $path
 * @property string $preview_link
 *
 * Relationships
 * @property BuilderPage|Collection $pages
 *
 * Scopes
 * @method static Builder|BuilderSite search(string $search) // scopeSearch
 * @method static Builder|BuilderSite whereToken(string $token) // scopeWhereToken
 */
class BuilderSite extends Model implements ImageableInterface
{
    use BaseAccountModelTrait;
    use ImageableTrait;
    use SoftDeletes;

    protected $table = 'builder_sites';

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

    public function getFileDir(): string
    {
        return 'contents';
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/builder_sites/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function Service(): BuilderSiteService
    {
        return new BuilderSiteService($this);
    }

    public function FileServiceFactory(string $dir = null): FileService
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO(), $dir);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(BuilderPage::class, 'builder_site_id', 'id');
    }

    public function scopeSearch(Builder $query, string $search = null): Builder
    {
        if ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('domain', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }

    public function scopeWhereToken(Builder $query, string $token): Builder
    {
        return $query->where('api_builder_key', '=', $token);
    }
}
