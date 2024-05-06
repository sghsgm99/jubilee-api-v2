<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FacebookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class FacebookAdLibraryController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('facebook-ad-library', [FacebookAdLibraryController::class, 'getAdLibrary']);
    }

    public function getAdLibrary()
    {
        $ads = FacebookService::getAdLibrary();
        return $ads;
    }
}
