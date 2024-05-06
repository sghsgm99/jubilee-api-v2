<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use App\Interfaces\ImageableInterface;
use App\Models\Services\Factories\FileServiceFactory;
use App\Models\Services\GoogleImageModelService;
use App\Models\Enums\StorageDiskEnum;

class GoogleImage extends Model implements ImageableInterface
{
    use HasFactory;
    use SoftDeletes;
    use BaseAccountModelTrait;
    use ImageableTrait;

    protected $table = 'google_images';

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
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function Service(): GoogleImageModelService
    {
        return new GoogleImageModelService($this);
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/google/images";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function FileServiceFactory(string $dir = null)
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO(), $dir);
    }

    public function scopeSearch(
        Builder $query,
        int $type = null,
        string $search = null,
        string $sort = null,
        string $sort_type = null
    )   
    {
        if ($type) {
            $query->where('type', $type);
        }

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
