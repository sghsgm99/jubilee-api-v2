<?php

namespace App\Models;

use App\Interfaces\ImageableInterface;
use App\Models\Enums\StorageDiskEnum;
use App\Models\Services\ArticleQuizzesService;
use App\Models\Services\Factories\FileServiceFactory;
use App\Traits\BaseAccountModelTrait;
use App\Traits\ImageableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ArticleQuizzes
 *
 * Database Fields
 * @property int $id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property int $article_id
 * @property int $account_id
 * @property string $title
 * @property string $description
 * @property string $choices
 * @property string $answer
 * @property string $answer_description
 * @property int $order
 * @property int|null $external_sync_id
 * @property string|null $external_sync_data
 * @property string|null $external_sync_image
 */
class ArticleQuizzes extends Model implements ImageableInterface
{
    use HasFactory;
    use SoftDeletes;
    use ImageableTrait;
    use BaseAccountModelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'article_id',
        'title',
        'external_sync_id',
        'external_sync_data',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'choices' => 'array',
        'external_sync_data' => 'array',
    ];

    public function Service(): ArticleQuizzesService
    {
        return new ArticleQuizzesService($this);
    }

    public function getRootDestinationPath(string $dir = null): string
    {
        $rootPath = "/articles/quizzes/{$this->id}";

        if ($dir) {
            $rootPath .= '/' . trim($dir, '/');
        }

        return $rootPath;
    }

    public function getExternalSyncImageAttribute(): ?string
    {
        if (isset($this->attributes['external_sync_image']) && $this->attributes['external_sync_image']) {
            return $this->attributes['external_sync_image'];
        }

        if (! $this->external_sync_id && ! $this->external_sync_data) {
            return null;
        }

        return "https://explore.reference.com/content/{$this->external_sync_data['gallery_id']}/{$this->external_sync_data['s3path']}";
    }

    public function FileServiceFactory(string $dir = null)
    {
        return FileServiceFactory::resolve($this, StorageDiskEnum::PUBLIC_DO(), $dir);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
