<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCopymaticRequest;
use App\Models\Services\CopymaticService;
use App\Services\ResponseService;

class CopymaticController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('copymatic', [CopymaticController::class, 'getCopyMaticData']);
    }

    public function getCopyMaticData(CreateCopymaticRequest $request)
    {
        return ResponseService::serviceUnavailable();
        /**
         * NOTE: commented lines are still awaiting for api activation/payment
         * will remove the comment once everything is working
         */
        // $copymaticservice = new CopymaticService;
        // $copymatic_data = $copymaticservice->fetchCopymaticData($request->validated());

        // return $copymatic_data;
    }
}
