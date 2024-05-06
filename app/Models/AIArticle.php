<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AIArticle extends Model
{
    use HasFactory;

    protected $appends = [];

    protected $table = 'aiarticles';

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

    public function scopeSearch(
        Builder $query,
        string $search = null
    ) {
        if ($search) {
            $query->where('title', 'like', '%' . $search . '%');
        }

        return $query;
    }
}
