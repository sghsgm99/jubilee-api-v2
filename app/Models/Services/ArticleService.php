<?php

namespace App\Models\Services;

use App\Models\Article;
use App\Models\ArticleGallery;
use App\Models\ArticleQuizzes;
use App\Models\ArticleScroll;
use App\Models\Campaign;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\Enums\ArticleTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\User;
use App\Services\ResponseService;
use App\Traits\ImageModelServiceTrait;
use GuzzleHttp\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ArticleService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var Article
     */
    private $article;

    public function __construct(Article $article)
    {
        $this->article = $article;
        $this->model = $article; // required
    }

    public static function create(
        ?User $user,
        ArticleStatusEnum $status,
        ArticleTypeEnum $type,
        string $title,
        string $content = null,
        int $toggle_length = null,
        array $images = []
    ) {
        $article = new Article();

        $article->user_id = $user->id ?? null;
        $article->account_id = auth()->user()->account_id;
        $article->title = $title;
        $article->content = $content;
        $article->toggle_length = $toggle_length;
        $article->type = $type;
        $article->status = $status;
        $article->save();

        foreach ($images as $image) {
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
            $file = $article->FileServiceFactory()->uploadFile($image, $filename);

            $article->Service()->attachImage($image, $file['name']);
        }

        return $article;
    }

    public function duplicate(ArticleTypeEnum $typeEnum)
    {
        $newArticle = ArticleService::create(
            auth()->user(),
            ArticleStatusEnum::DRAFT(),
            $typeEnum,
            "{$this->article->title} - Copy",
            $this->article->content,
            $this->article->toggle_length,
            []
        );

        $this->article->Service()->cloneImages($newArticle);

        if ($newArticle->type->is(ArticleTypeEnum::POST())) {
            return $newArticle;
        }

        if ($this->article->type->is(ArticleTypeEnum::QUIZZES())) {
            $this->article->quizzes->each(function($item, $key) use ($newArticle) {
                $title = "{$item->title} - Copy";

                if ($newArticle->type->is(ArticleTypeEnum::QUIZZES())) {
                    $duplicate = $item->replicate()->fill([
                        'article_id' => $newArticle->id,
                        'title' => $title,
                        'external_sync_id' => null,
                        'external_sync_image' => null,
                        'external_sync_data' => null,
                    ]);
                    $duplicate->save();
                }

                if ($newArticle->type->is(ArticleTypeEnum::INFINITE_SCROLL())) {
                    $duplicate = ArticleScrollService::create(
                        $newArticle,
                        $title,
                        $item->description,
                        null,
                        null,
                        $item->order
                    );
                }

                if ($newArticle->type->is(ArticleTypeEnum::GALLERY())) {
                    $duplicate = ArticleGalleryService::create(
                        $newArticle,
                        $title,
                        $item->description,
                        null,
                        $item->order
                    );
                }

                $item->Service()->cloneImages($duplicate);
            });
        }

        if ($this->article->type->is(ArticleTypeEnum::INFINITE_SCROLL())) {
            $this->article->scrolls->each(function($item, $key) use ($newArticle) {
                $title = "{$item->title} - Copy";

                if ($newArticle->type->is(ArticleTypeEnum::QUIZZES())) {
                    $duplicate = ArticleQuizzesService::create(
                        $newArticle,
                        $title,
                        $item->description,
                        '',
                        '',
                        null,
                        null,
                        $item->order
                    );
                }

                if ($newArticle->type->is(ArticleTypeEnum::INFINITE_SCROLL())) {
                    $duplicate = $item->replicate()->fill([
                        'article_id' => $newArticle->id,
                        'title' => $title,
                        'external_sync_id' => null,
                        'external_sync_image' => null,
                        'external_sync_data' => null,
                    ]);
                    $duplicate->save();
                }

                if ($newArticle->type->is(ArticleTypeEnum::GALLERY())) {
                    $duplicate = ArticleGalleryService::create(
                        $newArticle,
                        $title,
                        $item->description,
                        null,
                        $item->order
                    );
                }

                $item->Service()->cloneImages($duplicate);
            });
        }

        if ($this->article->type->is(ArticleTypeEnum::GALLERY())) {
            $this->article->galleries->each(function($item, $key) use ($newArticle) {
                $title = "{$item->title} - Copy";

                if ($newArticle->type->is(ArticleTypeEnum::QUIZZES())) {
                    $duplicate = ArticleQuizzesService::create(
                        $newArticle,
                        $title,
                        $item->description,
                        '',
                        '',
                        null,
                        null,
                        $item->order
                    );
                }

                if ($newArticle->type->is(ArticleTypeEnum::INFINITE_SCROLL())) {
                    $duplicate = ArticleScrollService::create(
                        $newArticle,
                        $title,
                        $item->description,
                        null,
                        null,
                        $item->order
                    );
                }

                if ($newArticle->type->is(ArticleTypeEnum::GALLERY())) {
                    $duplicate = $item->replicate()->fill([
                        'article_id' => $newArticle->id,
                        'title' => $title,
                        'external_sync_id' => null,
                        'external_sync_image' => null,
                        'external_sync_data' => null,
                    ]);
                    $duplicate->save();
                }

                $item->Service()->cloneImages($duplicate);
            });
        }

        return $newArticle;
    }

    public function update(
        ?User $user,
        string $title,
        string $slug,
        string $content = null,
        int $toggle_length = null,
        ArticleStatusEnum $status
    ) {
        $this->article->user_id = $user->id ?? null;
        $this->article->title = $title;
        $this->article->content = $content;
        $this->article->toggle_length = $toggle_length;
        $this->article->status = $status;

        if (auth()->user()->role_id->is(RoleTypeEnum::ROOT()) || auth()->user()->role_id->is(RoleTypeEnum::ADMINISTRATOR())) {
            $this->article->slug = Str::slug($slug);
        }

        $this->article->save();

        ArticleHistoryService::create($this->article);

        return $this->article->fresh();
    }

    public function syncCategories(array $category_ids = [])
    {
        if (empty($category_ids)) {
            return $this->article->categories()->detach();
        }

        return $this->article->categories()->sync($category_ids);
    }

    public function syncTags(array $tag_ids = [])
    {
        if (empty($tag_ids)) {
            return $this->article->tags()->detach();
        }

        return $this->article->tags()->sync($tag_ids);
    }

    public function syncMenus(array $menu_ids = [])
    {
        if (empty($menu_ids)) {
            return $this->article->menus()->detach();
        }

        return $this->article->menus()->sync($menu_ids);
    }

    public function uploadImages(array $images = [])
    {
        foreach ($images as $image) {
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
            $file = $this->article->FileServiceFactory()->uploadFile($image, $filename);

            $this->article->Service()->attachImage($image, $file['name']);
        }

        return $this->article->fresh()->images;
    }

    public function uploadContentImages(array $images = [])
    {
        $files = [];
        foreach ($images as $image) {
            $filename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME) . '_' . time();
            $files[] = $this->article->FileServiceFactory($this->article->getContentImagesDir())->uploadFile($image, $filename);
        }

        return $files;
    }

    /**
     * @param string|null $dir
     * @return array
     */
    public function getFilesByDirectory(string $dir = null)
    {
        return $this->article->FileServiceFactory($dir)->getDirectoryFiles();
    }

    public function deleteContentImage(string $filename, string $dir = null)
    {
        $this->article->FileServiceFactory($dir)->deleteFile($filename);
    }

    public function updateSeo(
        $title,
        $keyword,
        $description,
        $tags
    ) {
        SeoService::updateOrCreate(
            $this->article,
            $title,
            $keyword,
            $description,
            $tags
        );

        return $this->article->fresh()->seo;
    }

    public function published()
    {
        ResponseService::serviceUnavailable();
        if ($this->article->status->is(ArticleStatusEnum::DRAFT())) {
            abort(422, 'Article with Draft status cannot be published or unpublished.');
        }

        // $client = new Client();
        // $options = [
        //     'headers' => [
        //         'Content-Type' => 'application/json',
        //     ],
        //     'body' => json_encode([
        //         'id' => $this->article->id,
        //         'env' => (env('APP_ENV') === 'production') ? 'production' : 'staging'
        //     ])
        // ];

        // try {
        //     $response = $client->request('POST', config('deployer.article_endpoint'), $options);

        //     $this->incrementRevision();
        // } catch (\Throwable $exception) {
        //     abort(500, 'Bad Request: ' . $exception->getMessage());
        // }

//        return true;
    }

    public function delete(): bool
    {
        if (Campaign::where('article_id', $this->article->id)->exists()) {
            return false;
        }

        $this->article->status = ArticleStatusEnum::TRASH;
        $this->article->save();
        $this->article->delete();

        return true;
    }

    public static function BulkDelete(array $ids)
    {
        $failedDelete = [];
        $successDelete = [];

        foreach ($ids as $id) {

            $article = Article::find($id);


            if ($article->status->isNot(ArticleStatusEnum::PUBLISHED())) {
                if (!$article->Service()->delete()) {
                    $failedDelete[] = [
                        "id" => $id,
                        "title" => $article->title,
                        "status" => "Unarchived",
                        "message" => "Article cannot be deleted because of a relationship data with Campaigns"
                    ];
                } else {
                    $successDelete[] = [
                        "id" => $id,
                        "title" => $article->title,
                        "status" => "Archived",
                        "message" => "Article was archived."
                    ];
                }
            } else {
                $failedDelete[] = [
                    "id" => $id,
                    "title" => $article->title,
                    "status" => "Unarchived",
                    "message" => "Article cannot be deleted because the status is published"
                ];
            }
        }

        $response = [
            "archived" => $successDelete,
            "unarchived" => $failedDelete
        ];
        return $response;
    }

    public static function gloabalSearch(string $search)
    {
        $articles = Article::with('tags')->where('title', 'like', '%'.$search.'%')
        ->orWhere('content', 'like', '%'.$search.'%')
        ->orWhereHas('tags', function($q) use ($search){
            $q->where('label', 'like', '%'.$search.'%');
        })
        ->get();

        return $articles;
    }

    public function updateFeatured()
    {
        $this->article->is_featured = !$this->article->is_featured;
        $this->article->save();

        return $this->article->fresh();
    }

    public function updateTrending()
    {
        $this->article->is_trending = !$this->article->is_trending;
        $this->article->save();

        return $this->article->fresh();
    }

    public function incrementRevision()
    {
        $this->article->revision += 1;
        $this->article->save();

        return $this->article->fresh();
    }
}
