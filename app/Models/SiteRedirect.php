<?php

namespace App\Models;

use App\Models\Services\SiteRedirectService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class SiteRedirect
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $site_id
 * @property string $source
 * @property string $destination
 * @property string $code
 *
 * Relationship
 * @property Site $site
 */
class SiteRedirect extends Model
{
    use HasFactory;
    use SoftDeletes;

    public function Service(): SiteRedirectService
    {
        return new SiteRedirectService($this);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
