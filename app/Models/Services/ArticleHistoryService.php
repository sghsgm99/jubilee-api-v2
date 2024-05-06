<?php

namespace App\Models\Services;

use App\Models\Article;
use App\Models\ArticleHistory;

class ArticleHistoryService extends ModelService
{
    /**
     * @var ArticleHistory
     */
    private $articleHistory;

    public function __construct(ArticleHistory $articleHistory)
    {
        $this->articleHistory = $articleHistory;
        $this->model = $articleHistory; // required
    }

    public static function create(Article $article)
    {
        $articleHistory = new ArticleHistory;

        $articleHistory->history = [
            'title' => $article->title,
            'slug' => $article->slug,
            'content' => $article->content,
            'toggle_length' => $article->toggle_length,
            'status' => $article->status,
            'user_id' => $article->user_id,
            'account_id' => $article->account_id,
        ];
        $articleHistory->article_id = $article->id;
        $articleHistory->user_id = auth()->id();
        $articleHistory->account_id = auth()->user()->account_id;
        $articleHistory->save();

        return $articleHistory;
    }
}
