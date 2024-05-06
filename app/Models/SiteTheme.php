<?php

namespace App\Models;

use App\Models\Services\SiteThemeService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * SiteTheme class.
 *
 * Database Fields
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string $handle
 * @property int $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Scopes
 * @method static Builder|SiteTheme whereActive()
 */
class SiteTheme extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function Service(): SiteThemeService
    {
        return new SiteThemeService($this);
    }

    public function scopeWhereActive(Builder $builder)
    {
        return $builder->where('status', '=', 1);
    }
}
