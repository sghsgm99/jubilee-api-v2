<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadBuilderFileRequest;
use App\Models\BuilderSite;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Route;

class BuilderFileManagerController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('builders-file/{builderSite}', [BuilderFileManagerController::class, 'upload']);
        Route::delete('builders-file/{builderSite}/{filename}', [BuilderFileManagerController::class, 'delete']);
        Route::get('builders-file/{builderSite}/{filename}', [BuilderFileManagerController::class, 'get']);
        Route::get('builders-file/{builderSite}', [BuilderFileManagerController::class, 'collection']);

    }

    public function collection(BuilderSite $builderSite)
    {
        return ResponseService::success(
            'Success',
            $builderSite->FileServiceFactory($builderSite->getFileDir())->getDirectoryFiles()
        );
    }

    public function get(BuilderSite $builderSite, string $filename)
    {
        return ResponseService::success(
            'Success',
            $builderSite->FileServiceFactory($builderSite->getFileDir())->getFilePath($filename)
        );
    }

    public function upload(UploadBuilderFileRequest $request, BuilderSite $builderSite)
    {
        $files = [];
        $images = $request->validated()['images'];

        foreach ($images as $image) {
            $files[] = $builderSite->FileServiceFactory($builderSite->getFileDir())->uploadFile(
                $image,
                md5_file($image)
            );
        }

        return ResponseService::success('File uploaded', $files);
    }

    public function delete(BuilderSite $builderSite, string $filename)
    {
        $builderSite->FileServiceFactory($builderSite->getFileDir())->deleteFile($filename);

        return ResponseService::success('File was deleted');
    }
}
