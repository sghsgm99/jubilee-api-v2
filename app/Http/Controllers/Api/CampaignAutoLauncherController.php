<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Services\ResponseService;
use App\Http\Requests\UploadCSVFileRequest;

class CampaignAutoLauncherController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('campaignautolauncher/parse', [CampaignAutoLauncherController::class, 'parse']);
    }

    public function parse(UploadCSVFileRequest $request)
    {
        $path = $request->file('csv_file')->getRealPath();
        $data = array_map('str_getcsv', file($path));

        $parse_data = [];

        foreach ($data as $items) {
            $parse_data[] = [
                'campaign' => $items[0],
                'adset' => $items[1],
                'ad' => $items[2]
            ];
        }

        return $parse_data;
    }
}
