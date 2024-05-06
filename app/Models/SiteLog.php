<?php

namespace App\Models;

use App\Scopes\AccountScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Services\SiteLogService;

class SiteLog extends Model
{
    use HasFactory;

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
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();
    }

    public function Service(): SiteLogService
    {
        return new SiteLogService($this);
    }

    /**
     * Relationship to the Site Model.
     *
     * @return BelongsTo
     */
    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
    
    public function scopeSearch(
        Builder $query,
        string $search = null
    )
    {
        if ($search) {
            $query->where(function ($q) use($search) {
                $q->where('type', 'like', '%'.$search.'%');
            });
        }

        return $query;
    }
}
