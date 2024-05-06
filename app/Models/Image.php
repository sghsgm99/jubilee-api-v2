<?php

namespace App\Models;

use App\Models\Services\ImageService;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Image Class
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $account_id
 * @property int $imageable_id
 * @property string $imageable_type
 * @property string $name
 * @property string $extension
 * @property string $mime
 * @property float $size
 * @property bool $is_featured
 *
 * Accessors
 * @property-read string $path // getPathAttribute
 *
 * Scopes
 * @method static Builder|Image whereId($id) // scopeWhereId
 */
class Image extends Model
{
    use HasFactory;
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $fillable = [
        'imageable_id',
        'imageable_type',
        'name',
        'extension',
        'mime',
        'size',
        'account_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_featured' => 'boolean'
    ];

    public function getPathAttribute()
    {
        return $this->imageable->FileServiceFactory()->getFilePath($this->name);
    }

    public function getLocalFilePath()
    {
        return $this->imageable->FileSErviceFactory()->getLocalFilePath($this->name);
    }

    public function getFaviconImageAttribute()
    {
        return $this->imageable->FileSErviceFactory()->getFilePath('favicon/' . $this->name);
    }

    public function getLogoImageAttribute()
    {
        return $this->imageable->FileSErviceFactory()->getFilePath('logo/' . $this->name);
    }

    public function Service(): ImageService
    {
        return new ImageService($this);
    }

    /**
     * @return MorphTo
     */
    public function imageable()
    {
        return $this->morphTo();
    }

    public function scopeWhereId(Builder $query, int $id)
    {
        return $query->where('id', '=', $id);
    }
}
