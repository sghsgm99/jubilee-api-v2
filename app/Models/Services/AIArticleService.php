<?php

namespace App\Models\Services;

use App\Models\AIArticle;
use App\Services\ResponseService;

class AIArticleService extends ModelService
{
    private $aiarticle;

    public function __construct(AIArticle $aiarticle)
    {
        $this->aiarticle = $aiarticle;
        $this->model = $aiarticle; // required
    }

    public static function create(
        string $title,
        string $content
    ) {
        $aiarticle = new AIArticle();

        $aiarticle->title = $title;
        $aiarticle->content = $content;
        $aiarticle->save();

        return $aiarticle;
    }
}
