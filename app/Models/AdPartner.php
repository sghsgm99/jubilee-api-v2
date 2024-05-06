<?php

namespace App\Models;

use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AdPartner
 *
 * Database Fields
 * @property int $id
 * @property int $user_id
 * @property int $account_id
 * @property string $partner
 * @property array $config
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class AdPartner extends Model
{
    use HasFactory;
    use BaseAccountModelTrait;

    protected $table = 'adpartner';

    protected $fillable = [
        'partner',
        'config'
    ];

    protected $casts = [
        'config' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }
}
