<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\UploadedFile;
use App\Services\ResponseService;
use App\Http\Controllers\Controller;
use App\Http\Resources\GoogleImageResource;
use App\Services\OpenAIService;
use App\Services\FileService;
use App\Services\StableDiffusionAIService;
use App\Models\Enums\StorageDiskEnum;
use DateTime;

class AICreatorController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('aicreator/generateAd', [AICreatorController::class, 'generateAd']);
        Route::post('aicreator/generateArticle', [AICreatorController::class, 'generateArticle']);
        Route::post('aicreator/upload-ai-image', [AICreatorController::class, 'uploadAIImage']);
        Route::get('aicreator/facebookAd', [AICreatorController::class, 'getFacebookAd']);
    }

    public function generateAd(Request $request)
    {
        $prompt = $request->input('prompt');
        $title = $request->input('title');
        $amount = $request->input('amount');

        return [
            'text' => OpenAIService::resolve()->generateAIText($prompt),
            'image' => StableDiffusionAIService::resolve()->generateAIImageEx($title, $amount)
        ];
    }

    public function generateArticle(Request $request)
    {
        $title = $request->input('title');
        $tone = $request->input('tone', 'professional');
        $openAIService = OpenAIService::resolve();

        $text = "generate article - title: `$title`, write in `$tone` tone";
        
        return [
            'text' => $openAIService->generateAIText($text, 500)
        ];
    }

    public function uploadAIImage(Request $request)
    {
        $aiImages = $request->input('aiImages');
        $width = $request->input('width', 1080);
        $height = $request->input('height', 1080);

        $rootPath = "/facebook/images";
        $datetime = new DateTime();
        $timestamp = $datetime->getTimestamp();
        
        $imageInfos = [];

        foreach ($aiImages as $index => $aiImage) {
            $filename = "img".$timestamp."-".$index;

            $imageInfos[] = FileService::main(StorageDiskEnum::PUBLIC_DO(), $rootPath)->uploadResizeImage($aiImage['url'], $filename, $width, $height);
        }

        return $imageInfos;
    }

    public function getFacebookAd(Request $request)
    {
        $keyword = $request->input('keyword');
        $prompt = $keyword . " - generate facebook ad primary text, facebook ad headline, facebook ad description";
        $openAIService = OpenAIService::resolve();

        return [
            'text' => $openAIService->generateAIText($prompt),
            //'image' => $openAIService->generateAIImage($keyword, 1, '1024x1024')
            'image' => StableDiffusionAIService::resolve()->generateAIImageEx($keyword, 1, 1024, 1024)
        ];
    }
}
