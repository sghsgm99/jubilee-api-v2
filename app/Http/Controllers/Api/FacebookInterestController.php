<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacebookAdInterestSuggestionRequest;
use App\Http\Requests\CreateCampaignFacebookAdInterestSuggestionRequest;
use App\Models\Services\FacebookAdInterestSuggestionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class FacebookInterestController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('facebook-interest/suggestion', [FacebookInterestController::class, 'targetSearchAdInterestSuggestionAdSet']);

        Route::post('facebook-interest/suggestion/create-campaign', [FacebookInterestController::class, 'createCampaign']);
    }

    public function targetSearchAdInterestSuggestionAdSet(FacebookAdInterestSuggestionRequest $request)
    {
        return FacebookAdInterestSuggestionService::targetingSearchInterestSuggestionFacebook($request->validated()['interest_list']);
    }

    public function createCampaign(CreateCampaignFacebookAdInterestSuggestionRequest $request)
    {
        return FacebookAdInterestSuggestionService::createCampaign($request->validated()['interest_list'], $request->validated()['facebook_campaign_id']);
    }
}
