<?php

namespace App\Models\Services;

use App\Models\Article;
use App\Models\ArticleScroll;
use App\Models\Enums\ArticleTypeEnum;
use App\Traits\ImageModelServiceTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleScrollService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var ArticleScroll
     */
    private $article_scroll;

    public function __construct(ArticleScroll $article_scroll)
    {
        $this->article_scroll = $article_scroll;
        $this->model = $article_scroll; // required
    }

    public static function create(
        Article $article,
        string $title,
        string $description = null,
        UploadedFile $image = null,
        string $image_description = null,
        int $order = null
    ) {

        if(!$article->type->is(ArticleTypeEnum::INFINITE_SCROLL())) {
            return [
                'error' => true,
                'message' =>  'Content type not Infinite Scroll'
            ];
        }

        try {
            DB::beginTransaction();


            $article_scroll = new ArticleScroll();
            $article_scroll->title = $title;
            $article_scroll->description = $description;
            $article_scroll->image_description = $image_description;
            $article_scroll->order = $order;
            $article_scroll->article()->associate($article);
            $article_scroll->account()->associate(Auth()->user()->account);
            $article_scroll->save();

            if($image) {
                $filename = 'image_'.$article_scroll->id;
                $file = $article_scroll->FileServiceFactory()->uploadFile($image, $filename);
                $article_scroll->Service()->attachImage($image, $file['name']);
            }

            DB::commit();

            return $article_scroll;


        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage(),
            ];
        }

    }

    public function update(
        string $title,
        string $description = null,
        UploadedFile $image = null,
        string $image_description = null
    )
    {
        try {
            DB::beginTransaction();

            $this->article_scroll->title = $title;
            $this->article_scroll->description = $description;
            $this->article_scroll->image_description = $image_description;
            $this->article_scroll->save();

            if ($image) {
                $filename = 'image_'.$this->article_scroll->id;
                $file = $this->article_scroll->FileServiceFactory()->uploadFile($image, $filename);

                if ($file) {
                    $this->article_scroll->Service()->attachImage($image, $file['name']);
                }
            }

            DB::commit();

            return $this->article_scroll->fresh();

        } catch (\Throwable $th) {
            DB::rollBack();
            return [
                'error' => true,
                'message' => $th->getMessage(),
            ];
        }
    }

    public function updateSortOrder($order = 0)
    {
        $this->article_scroll->order = $order;
        $this->article_scroll->save();

        return $this->article_scroll->fresh();
    }
}
