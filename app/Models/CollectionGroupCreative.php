<?php

namespace App\Models;

use App\Models\Enums\CollectionCreativeTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CollectionGroupCreative extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'type' => CollectionCreativeTypeEnum::class
    ];

    public function creative()
    {
        return $this->belongsTo(CollectionCreative::class, 'creative_id');
    }

    public function group()
    {
        return $this->belongsTo(CollectionGroup::class, 'group_id');
    }

    public function scopeSearch(
        Builder $query,
        int $group_id,
        string $sort = null,
        string $sort_type = null,
        int $type = null,
        string $search = null
    )
    {
        $query->where('group_id', $group_id);

        if($sort) {
            $query->orderBy($sort, $sort_type);
        }

        if($type) {
            $query->whereHas('creative', function($q) use($type) {
                $q->where('type', $type);
            });
        }

        if($search) {
            $query->where('data', 'like', "%{$search}%");
        }

        return $query;
    }
}
