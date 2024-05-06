<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateReadableApiRequest;
use App\Models\Services\ReadableApiService;
use App\Services\ResponseService;

class ReadableApiController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('readableapi', [ReadableApiController::class, 'getReadableApiData']);
    }

    public function getReadableApiData(CreateReadableApiRequest $request)
    {
        return ResponseService::serviceUnavailable();
        /**
         * NOTE: commented lines are still awaiting for api activation/payment
         * will remove the comment once everything is working
         */

        // $readableservice = new ReadableApiService;
        // $readableapi_data = $readableservice->fetchReadableApiData($request->validated());

        // return $readableapi_data;
    }
}
