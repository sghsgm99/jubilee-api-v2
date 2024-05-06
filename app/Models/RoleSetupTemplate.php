<?php

namespace App\Models;

use App\Models\Enums\RoleTypeEnum;
use App\Models\Services\RoleSetupTemplateService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Database Fields
 * @property int $id
 * @property int $role_id
 * @property string $setup_name
 * @property string $setup
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Scopes
 * @method static Builder|RoleSetupTemplate whereRoleId($value) // scopeWhereRoleId
 */
class RoleSetupTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'role_id',
        'setup_name',
        'setup',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'setup' => 'array'
    ];

    public function scopeWhereRoleId(Builder $query, RoleTypeEnum $enum)
    {
        return $query->where('role_id', '=', $enum);
    }

    public function Service(): RoleSetupTemplateService
    {
        return new RoleSetupTemplateService($this);
    }
}
