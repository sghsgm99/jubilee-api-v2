<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCopyscapeRequest;
use App\Models\Services\CopyscapeService;
use App\Services\ResponseService;

class CopyscapeController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('copyscape', [CopyscapeController::class, 'getCopyScapeData']);
        Route::get('copyscape-balance', [CopyscapeController::class, 'getCopyScapeBalance']);
    }

    public function getCopyScapeBalance(CreateCopyscapeRequest $request)
    {
        return ResponseService::serviceUnavailable();
        /**
         * NOTE: commented lines are still awaiting for api activation/payment
         * will remove the comment once everything is working
         */
        // $copyscapeservice = new CopyscapeService;
        // $copymatic_data = $copyscapeservice->fetchCopyscapeBalance($request->validated());

        // return $copymatic_data;
    }

    public function getCopyScapeData(CreateCopyscapeRequest $request)
    {
        return ResponseService::serviceUnavailable();

        /**
         * NOTE: commented lines are still awaiting for api activation/payment
         * will remove the comment once everything is working
         */
        // $copyscapeservice = new CopyscapeService;
        // $copyscape_data = $copyscapeservice->fetchTextSearchRequest($request->validated());

        // return $copyscape_data;
    }
}
