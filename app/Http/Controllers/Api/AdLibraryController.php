<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AdLibraryService;

class AdLibraryController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('adlibrary/search', [AdLibraryController::class, 'search']);
    }

    public function search(Request $request)
    {
      return AdLibraryService::resolve()->getAdLibrary();
    }
}
