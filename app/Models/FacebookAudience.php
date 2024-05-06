<?php

namespace App\Models;

use App\Models\Enums\FacebookAudienceTypeEnum;
use App\Models\Services\FacebookLookalikeService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacebookAudience extends Model
{
    use HasFactory;
    use BaseAccountModelTrait;
    use SoftDeletes;


    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'audience_type' => FacebookAudienceTypeEnum::class,
        'setup_details' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): FacebookLookalikeService
    {
        return new FacebookLookalikeService($this);
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }
}
