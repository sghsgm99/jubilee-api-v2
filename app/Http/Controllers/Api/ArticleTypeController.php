<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUpdateArticleGalleryRequest;
use App\Http\Requests\CreateUpdateArticleQuizRequest;
use App\Http\Requests\CreateUpdateArticleScrollRequest;
use App\Http\Resources\ArticleGalleryResource;
use App\Http\Resources\ArticleQuizzesResource;
use App\Http\Resources\ArticleScrollResource;
use App\Models\Article;
use App\Models\ArticleGallery;
use App\Models\ArticleQuizzes;
use App\Models\ArticleScroll;
use App\Models\Services\ArticleGalleryService;
use App\Models\Services\ArticleQuizzesService;
use App\Models\Services\ArticleScrollService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ArticleTypeController extends Controller
{

    public static function apiRoutes()
    {
        Route::get('articles/{article}/type/quizzes', [ArticleTypeController::class, 'getArticleQuizzes']);
        Route::get('articles/type/quizzes/{article_quiz}', [ArticleTypeController::class, 'getArticleQuiz']);
        Route::post('articles/{article}/type/quizzes', [ArticleTypeController::class, 'createArticleQuiz']);
        Route::post('articles/type/quizzes/{article_quiz}', [ArticleTypeController::class, 'updateArticleQuiz']);
        Route::put('articles/type/quizzes/sort', [ArticleTypeController::class, 'sortArticleQuizzes']);
        Route::delete('articles/type/quizzes/{article_quiz}', [ArticleTypeController::class, 'deleteArticleQuiz']);

        Route::get('articles/{article}/type/infinite-scroll', [ArticleTypeController::class, 'getArticleInfiniteScrolls']);
        Route::get('articles/type/infinite-scroll/{article_scroll}', [ArticleTypeController::class, 'getArticleScroll']);
        Route::post('articles/{article}/type/infinite-scroll', [ArticleTypeController::class, 'createArticleScroll']);
        Route::post('articles/type/infinite-scroll/{article_scroll}', [ArticleTypeController::class, 'updateArticleScroll']);
        Route::put('articles/type/infinite-scroll/sort', [ArticleTypeController::class, 'sortArticleScroll']);
        Route::delete('articles/type/infinite-scroll/{article_scroll}', [ArticleTypeController::class, 'deleteArticleScroll']);

        Route::get('articles/{article}/type/gallery', [ArticleTypeController::class, 'getArticleGalleries']);
        Route::get('articles/type/gallery/{article_gallery}', [ArticleTypeController::class, 'getArticleGallery']);
        Route::post('articles/{article}/type/gallery', [ArticleTypeController::class, 'createArticleGallery']);
        Route::post('articles/type/gallery/{article_gallery}', [ArticleTypeController::class, 'updateArticleGallery']);
        Route::put('articles/type/gallery/sort', [ArticleTypeController::class, 'sortArticleGallery']);
        Route::delete('articles/type/gallery/{article_gallery}', [ArticleTypeController::class, 'deleteArticleGallery']);
    }

    public function getArticleQuizzes(Article $article, Request $request)
    {
        return ArticleQuizzesResource::collection($article->quizzes);
    }

    public function getArticleQuiz(ArticleQuizzes $article_quiz)
    {
        return new ArticleQuizzesResource($article_quiz);
    }

    public function createArticleQuiz(Article $article, CreateUpdateArticleQuizRequest $request)
    {
        $quiz = ArticleQuizzesService::create(
            $article,
            $request->validated()['title'],
            $request->validated()['description'],
            $request->validated()['choices'],
            $request->validated()['answer'],
            $request->validated()['answer_description'],
            $request->image ?? null
        );

        if(isset($quiz['error'])) {
            return ResponseService::serverError($quiz['message']);
        }
        return ResponseService::successCreate('Quiz was created.', new ArticleQuizzesResource($quiz));
    }

    public function updateArticleQuiz(CreateUpdateArticleQuizRequest $request, ArticleQuizzes $article_quiz)
    {
        $quiz = $article_quiz->Service()->update(
            $request->validated()['title'],
            $request->validated()['description'],
            $request->validated()['choices'],
            $request->validated()['answer'],
            $request->validated()['answer_description'],
            $request->image ?? null
        );

        if(isset($quiz['error'])) {
            return ResponseService::serverError($quiz['message']);
        }
        return ResponseService::successCreate('Quiz was updated.', new ArticleQuizzesResource($quiz));
    }

    public function sortArticleQuizzes(Request $request)
    {
        if (empty($request->input('ids')) && !is_array($request->input('ids'))) {
            return ResponseService::clientError('Invalid data.');
        }

        $order = 1;
        foreach ($request->input('ids') as $id) {
            if ($article_quiz = ArticleQuizzes::find($id)) {
                $article_quiz->Service()->updateSortOrder($order);
                $order++;
            }
        }

        return ResponseService::success('Sort order updated.');
    }

    public function deleteArticleQuiz(ArticleQuizzes $article_quiz)
    {
        if($article_quiz->Service()->delete()) {
            return ResponseService::successCreate('Quiz was deleted.', []);
        }
        return ResponseService::clientError('Failed to delete Quiz');
    }

    public function getArticleInfiniteScrolls(Article $article, Request $request)
    {
        return ArticleScrollResource::collection($article->scrolls);
    }

    public function getArticleScroll(ArticleScroll $article_scroll)
    {
        return new ArticleScrollResource($article_scroll);
    }

    public function createArticleScroll(Article $article, CreateUpdateArticleScrollRequest $request)
    {
        $scroll = ArticleScrollService::create(
            $article,
            $request->validated()['title'],
            $request->validated()['description'],
            $request->image ?? null,
            $request->validated()['image_description']
        );
        if(isset($scroll['error'])) {
            return ResponseService::serverError($scroll['message']);
        }
        return ResponseService::successCreate('Infinite Scroll was created.', new ArticleScrollResource($scroll));
    }

    public function updateArticleScroll(ArticleScroll $article_scroll, CreateUpdateArticleScrollRequest $request)
    {
        $scroll = $article_scroll->Service()->update(
            $request->validated()['title'],
            $request->validated()['description'],
            $request->image ?? null,
            $request->validated()['image_description']
        );

        if(isset($scroll['error'])) {
            return ResponseService::serverError($scroll['message']);
        }
        return ResponseService::successCreate('Infinite Scroll was updated.', new ArticleScrollResource($scroll));
    }

    public function sortArticleScroll(Request $request)
    {
        if (empty($request->input('ids')) && !is_array($request->input('ids'))) {
            return ResponseService::clientError('Invalid data.');
        }

        $order = 1;
        foreach ($request->input('ids') as $id) {
            if ($article_scroll = ArticleScroll::find($id)) {
                $article_scroll->Service()->updateSortOrder($order);
                $order++;
            }
        }

        return ResponseService::success('Sort order updated.');
    }

    public function deleteArticleScroll(ArticleScroll $article_scroll)
    {
        if($article_scroll->Service()->delete()) {
            return ResponseService::successCreate('Infinite Scroll was deleted.', []);
        }
        return ResponseService::clientError('Failed to delete Infinite Scroll');
    }

    public function getArticleGalleries(Article $article, Request $request)
    {
        return ArticleGalleryResource::collection($article->galleries);
    }

    public function getArticleGallery(ArticleGallery $article_gallery)
    {
        return new ArticleGalleryResource($article_gallery);
    }

    public function createArticleGallery(Article $article, CreateUpdateArticleGalleryRequest $request)
    {
        $gallery = ArticleGalleryService::create(
            $article,
            $request->validated()['title'],
            $request->validated()['description'],
            $request->image ?? null,
        );
        if(isset($gallery['error'])) {
            return ResponseService::serverError($gallery['message']);
        }
        return ResponseService::successCreate('Gallery was created.', new ArticleGalleryResource($gallery));
    }

    public function updateArticleGallery(ArticleGallery $article_gallery, CreateUpdateArticleGalleryRequest $request)
    {
        $gallery = $article_gallery->Service()->update(
            $request->validated()['title'],
            $request->validated()['description'],
            $request->image ?? null,
        );

        if(isset($gallery['error'])) {
            return ResponseService::serverError($gallery['message']);
        }
        return ResponseService::successCreate('Gallery was updated.', new ArticleGalleryResource($gallery));
    }

    public function sortArticleGallery(Request $request)
    {
        if (empty($request->input('ids')) && !is_array($request->input('ids'))) {
            return ResponseService::clientError('Invalid data.');
        }

        $order = 1;
        foreach ($request->input('ids') as $id) {
            if ($article_gallery = ArticleGallery::find($id)) {
                $article_gallery->Service()->updateSortOrder($order);
                $order++;
            }
        }

        return ResponseService::success('Sort order updated.');
    }

    public function deleteArticleGallery(ArticleGallery $article_gallery)
    {
        if($article_gallery->Service()->delete()) {
            return ResponseService::successCreate('Gallery was deleted.', []);
        }
        return ResponseService::clientError('Failed to delete Gallery');
    }
}
