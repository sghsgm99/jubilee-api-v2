<?php

namespace App\Models\Services;

use App\Models\Article;
use App\Models\ArticleQuizzes;
use App\Models\Campaign;
use App\Models\Enums\ArticleStatusEnum;
use App\Models\Enums\ArticleTypeEnum;
use App\Models\Enums\RoleTypeEnum;
use App\Models\User;
use App\Traits\ImageModelServiceTrait;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArticleQuizzesService extends ModelService
{
    use ImageModelServiceTrait;

    /**
     * @var ArticleQuizzes
     */
    private $article_quizzes;

    public function __construct(ArticleQuizzes $article_quizzes)
    {
        $this->article_quizzes = $article_quizzes;
        $this->model = $article_quizzes; // required
    }

    public static function create(
        Article $article,
        string $title,
        string $description = null,
        string $choices,
        string $answer,
        string $answer_description = null,
        UploadedFile $image = null,
        int $order = null
    ) {
        if(!$article->type->is(ArticleTypeEnum::QUIZZES())) {
            return [
                'error' => true,
                'message' =>  'Content type not Quiz'
            ];
        }

        try {
            DB::beginTransaction();

            $choices = array_map('trim', explode(',', $choices));

            $article_quizzes = new ArticleQuizzes();
            $article_quizzes->title = $title;
            $article_quizzes->description = $description;
            $article_quizzes->choices = $choices;
            $article_quizzes->answer = $answer;
            $article_quizzes->answer_description = $answer_description;
            $article_quizzes->order = $order;
            $article_quizzes->article()->associate($article);
            $article_quizzes->account()->associate(Auth()->user()->account);
            $article_quizzes->save();

            if($image) {
                $filename = 'image_'.$article_quizzes->id;
                $file = $article_quizzes->FileServiceFactory()->uploadFile($image, $filename);
                $article_quizzes->Service()->attachImage($image, $file['name']);
            }

            DB::commit();

            return $article_quizzes;


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
        string $choices,
        string $answer,
        string $answer_description = null,
        UploadedFile $image = null
    )
    {
        try {
            DB::beginTransaction();

            $this->article_quizzes->title = $title;
            $this->article_quizzes->description = $description;
            $this->article_quizzes->choices = $choices;
            $this->article_quizzes->answer = $answer;
            $this->article_quizzes->answer_description = $answer_description;
            $this->article_quizzes->save();

            if ($image) {
                $filename = 'image_'.$this->article_quizzes->id;
                $file = $this->article_quizzes->FileServiceFactory()->uploadFile($image, $filename);

                if ($file) {
                    $this->article_quizzes->Service()->attachImage($image, $file['name']);
                }
            }


            DB::commit();

            return $this->article_quizzes->fresh();

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
        $this->article_quizzes->order = $order;
        $this->article_quizzes->save();

        return $this->article_quizzes->fresh();
    }
}
