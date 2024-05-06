<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Traits\BaseAccountModelTrait;
use App\Models\Services\GoogleCustomerModelService;
use App\Models\Enums\GenericStatusEnum;

class GoogleCustomer extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'google_customers';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'status' => GenericStatusEnum::class
    ];

    public function Service()
    {
        return new GoogleCustomerModelService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function campaign()
    {
        return $this->hasMany(GoogleCampaign::class, 'customer_id');
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        string $sort = null,
        string $sort_type = null,
        int $google_account = null,
        int $status = null
    )
    {
        if ($search) {
            $query->where('name', 'like', '%'.$search.'%');
        }

        if ($google_account){
            $query->where('google_account', '=', $google_account);
        }

        if ($status){
            $query->where('status', '=', $status);
        }

        if ($sort) {
            $query->orderBy($sort, $sort_type);
        }

        $query->orderBy('google_account', 'desc');
        
        return $query;
    }
}
