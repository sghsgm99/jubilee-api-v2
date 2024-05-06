<?php

namespace App\Models;

use App\Models\Services\ArticleHistoryService;
use App\Scopes\AccountScope;
use App\Traits\BaseAccountModelTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class ArticleHistory
 *
 * Database Fields
 * @property int $id
 * @property string $history
 * @property int $article_id
 * @property int $user_id
 * @property int $account_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 *
 * Relationships
 * @property User $user
 * @property Article $article
 */
class ArticleHistory extends Model
{
    use BaseAccountModelTrait;
    use SoftDeletes;

    protected $table = 'article_history';

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'history' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new AccountScope());
    }

    public function Service(): ArticleHistoryService
    {
        return new ArticleHistoryService($this);
    }

    /**
     * Relationship to the User Model.
     *
     * @return BelongsTo|User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship to the Article Model.
     *
     * @return BelongsTo|Article
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
}
