<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuilderSiteResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class CMSBuilderSiteController extends Controller
{
    public static function webhooks()
    {
        Route::get('cms/builder/site', [CMSBuilderSiteController::class, 'get']);
    }

    public function get(Request $request)
    {
        $builderSite = $request->input('builder_site');
        return new BuilderSiteResource($builderSite);
    }
}
