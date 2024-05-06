<?php

namespace App\Models;

use App\Models\Services\MROASService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ocodes extends Model
{
    use HasFactory;

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

    public function Service(): MROASService
    {
        return new MROASService($this);
    }

    public function site()
    {
        return $this->hasOne(related: 'App\Models\Site', foreignKey: 'id', localKey: 'site_id');
    }
}
