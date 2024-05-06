<?php

namespace App\Models\Services;

use App\Models\Article;
use App\Models\ArticleGallery;
use App\Models\Enums\ArticleTypeEnum;
use App\Traits\ImageModelServiceTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleGalleryService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var ArticleGallery
     */
    private $article_gallery;

    public function __construct(ArticleGallery $article_gallery)
    {
        $this->article_gallery = $article_gallery;
        $this->model = $article_gallery; // required
    }

    public static function create(
        Article $article,
        string $title,
        string $description = null,
        UploadedFile $image = null,
        int $order = null
    ) {

        if(!$article->type->is(ArticleTypeEnum::GALLERY())) {
            return [
                'error' => true,
                'message' =>  'Content type not Gallery'
            ];
        }

        try {
            DB::beginTransaction();


            $article_gallery = new ArticleGallery();
            $article_gallery->title = $title;
            $article_gallery->description = $description;
            $article_gallery->order = $order;
            $article_gallery->article()->associate($article);
            $article_gallery->account()->associate(Auth()->user()->account);
            $article_gallery->save();

            if($image) {
                $filename = 'image_'.$article_gallery->id;
                $file = $article_gallery->FileServiceFactory()->uploadFile($image, $filename);
                $article_gallery->Service()->attachImage($image, $file['name']);
            }

            DB::commit();

            return $article_gallery;


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
        UploadedFile $image = null
    )
    {
        try {
            DB::beginTransaction();

            $this->article_gallery->title = $title;
            $this->article_gallery->description = $description;
            $this->article_gallery->save();

            if ($image) {
                $filename = 'image_'.$this->article_gallery->id;
                $file = $this->article_gallery->FileServiceFactory()->uploadFile($image, $filename);

                if ($file) {
                    $this->article_gallery->Service()->attachImage($image, $file['name']);
                }
            }

            DB::commit();

            return $this->article_gallery->fresh();

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
        $this->article_gallery->order = $order;
        $this->article_gallery->save();

        return $this->article_gallery->fresh();
    }
}
