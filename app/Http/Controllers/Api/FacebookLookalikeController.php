<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateFacebookLookalikeRequest;
use App\Http\Requests\CreateUpdateFacebookCustomAudienceRequest;
use App\Http\Requests\UpdateFacebookLookalikeRequest;
use App\Http\Resources\FacebookAudienceCollectionResource;
use App\Http\Resources\FacebookAudienceResource;
use App\Models\Channel;
use App\Models\Enums\FacebookAudienceTypeEnum;
use App\Models\Enums\FacebookPageEventFilterValueEnum;
use App\Models\FacebookAudience;
use App\Models\Services\FacebookLookalikeService;
use App\Services\ResponseService;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

class FacebookLookalikeController extends Controller
{
    public static function apiRoutes()
    {
        // CUSTOM AUDIENCE ENDPOINTS
        Route::get('facebook-audience/{channel}', [FacebookLookalikeController::class, 'getFacebookAudience']);
        Route::get('facebook-audience/{channel}/get-facebook-audience/{facebook_audience_id}', [FacebookLookalikeController::class, 'getSingleFacebookAudience']);
        Route::get('facebook-audience/{channel}/get-facebook-pages', [FacebookLookalikeController::class, 'getFacebookPages']);
        Route::post('facebook-audience/{channel}/create-custom-audience', [FacebookLookalikeController::class, 'createFacebookCustomAudience']);
        Route::put('facebook-audience/{channel}/update-custom-audience/{facebook_audience}', [FacebookLookalikeController::class, 'updateFacebookCustomAudience']);
        Route::delete('facebook-audience/{channel}/delete-audience/{facebook_audience_id}', [FacebookLookalikeController::class, 'deleteFacebookAudience']);

        // LOOKALIKE AUDIENCE ENDPOINTS
        Route::post('facebook-audience/{channel}/create-lookalike-audience', [FacebookLookalikeController::class, 'createFacebookLookalikeAudience']);
        Route::put('facebook-audience/{channel}/update-lookalike-audience/{facebook_audience}', [FacebookLookalikeController::class, 'updateFacebookLookalikeAudience']);
        Route::get('facebook-audience/{channel}/get-country', [FacebookLookalikeController::class, 'getCountry']);

        // FOR DEVELOPMENT
        Route::post('facebook-lookalike/create-audience', [FacebookLookalikeController::class, 'createCustomAudience']);
        Route::post('facebook-lookalike/create-audience-user-list', [FacebookLookalikeController::class, 'createCustomAudienceUserList']);
        Route::post('facebook-lookalike/create-conversion-lookalike-audience', [FacebookLookalikeController::class, 'createConversionLookalikeAudience']);
        Route::post('facebook-lookalike/create-page-fan-lookalike-audience', [FacebookLookalikeController::class, 'createPageFanLookalikeAudience']);

    }

    public function getFacebookAudience(Channel $channel, Request $request)
    {
        $audiences = FacebookLookalikeService::getFacebookAudience(
            $channel,
            $request->custom ?? null,
            $request->search ?? null,
            $request->type ?? null,
            $request->source ?? null,
            $request->ad_account ?? null
        );

        return FacebookAudienceCollectionResource::collection($audiences);
    }

    public function getSingleFacebookAudience(Channel $channel, $facebook_audience_id)
    {
        $facebook_audience = FacebookAudience::where('audience_id', $facebook_audience_id)->first();

        if($facebook_audience) {
            $single = $facebook_audience->Service()->getSingleFacebookAudience();
            return new FacebookAudienceResource($single);
        }

        return FacebookLookalikeService::getAudienceFromPlatform($channel, $facebook_audience_id);
    }

    public function getFacebookPages(Channel $channel)
    {
        return FacebookLookalikeService::getFacebookPages($channel);
    }

    public function createFacebookCustomAudience(Channel $channel, CreateUpdateFacebookCustomAudienceRequest $request)
    {
        $audience = FacebookLookalikeService::createFacebookCustomAudience(
            $channel,
            $request->validated()['audience_name'],
            $request->validated()['audience_description'] ?? null,
            FacebookAudienceTypeEnum::memberByValue($request->validated()['audience_type']),
            $request->validated()['event_source_id'],
            $request->validated()['retention_days'],
            FacebookPageEventFilterValueEnum::memberByKey(str_replace('_', ' ', $request->validated()['event_filter_value'])),
            $request->validated()['audience_id'] ?? null,
            $request->validated()['ad_account'] ?? null
        );

        if($audience['error']) {
            return ResponseService::clientError('Custom Audience was not created.', $audience);
        }

        return ResponseService::successCreate('Custom Audience was created successfully.', new FacebookAudienceResource($audience));

    }

    public function updateFacebookCustomAudience(Channel $channel, FacebookAudience $facebook_audience, CreateUpdateFacebookCustomAudienceRequest $request)
    {
        $audience = $facebook_audience->Service()->updateFacebookCustomeAudience(
            $channel,
            $request->validated()['audience_name'],
            $request->validated()['audience_description'] ?? null,
            FacebookAudienceTypeEnum::memberByValue($request->validated()['audience_type']),
            $request->validated()['event_source_id'],
            $request->validated()['retention_days'],
            FacebookPageEventFilterValueEnum::memberByKey(str_replace('_', ' ', $request->validated()['event_filter_value']))
        );

        if($audience['error']) {
            return ResponseService::clientError('Custom Audience was not updated.', $audience);
        }

        return ResponseService::successCreate('Custom Audience was updated successfully.', new FacebookAudienceResource($audience));
    }

    public function deleteFacebookAudience(Channel $channel, $facebook_audience_id)
    {
        $audience = FacebookLookalikeService::deleteFacebookAudience(
            $channel,
            $facebook_audience_id
        );

        if(isset($audience['error'])) {
            return ResponseService::clientError('Custom Audience was not deleted.', $audience);
        }
        return ResponseService::successCreate('Custom Audience was deleted successfully.');
    }



    // ---------------------------------------------------------------------------------------------


    public function createFacebookLookalikeAudience(Channel $channel, CreateFacebookLookalikeRequest $request)
    {

        $audience = FacebookLookalikeService::createFacebookLookalikeAudience(
            $channel,
            $request->validated()['facebook_audience_id'],
            $request->validated()['audience_name'],
            $request->validated()['audience_description'] ?? '',
            FacebookAudienceTypeEnum::memberByValue($request->validated()['audience_type']),
            $request->validated()['starting_size'],
            $request->validated()['ending_size'],
            $request->validated()['country'],
            $request->validated()['audience_id'] ?? null,
        );

        if($audience['error']) {
            return ResponseService::clientError('Lookalike Audience was not created.', $audience);
        }

        return ResponseService::successCreate('Lookalike Audience was created successfully.', $audience);
    }

    public function updateFacebookLookalikeAudience(Channel $channel, FacebookAudience $facebook_audience, UpdateFacebookLookalikeRequest $request)
    {
        $audience = $facebook_audience->Service()->updateFacebookLookalikeAudience(
            $channel,
            $request->validated()['audience_name'],
            $request->validated()['audience_description'] ?? '',
        );

        if($audience['error']) {
            return ResponseService::clientError('Lookalike Audience was not updated.', $audience);
        }

        return ResponseService::successCreate('Lookalike Audience was updated successfully.', $audience);
    }


    public static function getCountry(Channel $channel, Request $request)
    {
        return $channel->Service()->targetingSearchCountry($request->country ?? null);
    }

}
