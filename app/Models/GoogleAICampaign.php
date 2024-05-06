<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Models\Services\GoogleAICampaignModelService;
use App\Traits\BaseAccountModelTrait;
use App\Traits\GoogleQueueableTrait;

class GoogleAICampaign extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;
    use GoogleQueueableTrait;

    protected $table = 'google_ai_campaigns';

    protected $fillable = [
        'title',
        'base_url',
        'budget',
        'bid'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    public function Service()
    {
        return new GoogleAICampaignModelService($this);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(GoogleCustomer::class);
    }

    public function scopeSearch(
        Builder $query,
        string $search = null,
        string $sort = null,
        string $sort_type = null
    )
    {
        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($sort) {
            $query->orderBy($sort, $sort_type);
        }

        return $query;
    }
}
