<?php

namespace App\Models\Services;

use App\Interfaces\SeoableInterface;
use App\Models\Seo;

class SeoService extends ModelService
{
    /**
     * @var Seo
     */
    private $seo;


    public function __construct(Seo $seo)
    {
        $this->seo = $seo;
        $this->model = $seo;
    }

    public static function updateOrCreate(
        SeoableInterface $seoable,
        $title,
        $keyword,
        $description,
        $tags
    )
    {
        if($seoable->seo) {
            // update
            $seoable->seo->update([
                'title' => $title,
                'keyword' => $keyword,
                'description' => $description,
                'tags' => $tags,
            ]);
        } else {
            // create
            $seoable->seo()->create([
                'title' => $title,
                'keyword' => $keyword,
                'description' => $description,
                'tags' => $tags,
                'account_id' => $seoable->account_id
            ]);
        }

        return $seoable->seo;
    }
}
