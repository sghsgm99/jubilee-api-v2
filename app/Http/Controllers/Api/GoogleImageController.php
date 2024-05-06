<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\UploadedFile;
use App\Services\ResponseService;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoogleImageResource;
use App\Models\GoogleImage;
use App\Models\Services\GoogleImageModelService;

class GoogleImageController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('google-images', [GoogleImageController::class, 'getCollection']);
        Route::post('google-images/upload-image', [GoogleImageController::class, 'uploadImage']);
        Route::post('google-images/upload-ai-image', [GoogleImageController::class, 'uploadAIImage']);
    }

    public function getCollection(Request $request)
    {
        $type = $request->input('type', null);
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $googleImages = GoogleImage::search(
            $type,
            $search,
            $sort,
            $sort_type
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleImageResource::collection($googleImages);
    }

    public function uploadImage(Request $request)
    {
        $googleImage = GoogleImageModelService::createUpload(
            $request->file('image') ?? null,
            $request->input('type', null)
        );

        return ResponseService::successCreate('Google Image was uploaded.', new GoogleImageResource($googleImage));
    }

    public function uploadAIImage(Request $request)
    {
        $type = $request->input('type', null);
        $images = $request->input('ai_images', null);

        $aiImages = [];
        foreach ($images as $image) {
            $aiImages[] = GoogleImageModelService::createUploadAI($image['url'], $type);
        }

        return GoogleImageResource::collection($aiImages);
    }
}
