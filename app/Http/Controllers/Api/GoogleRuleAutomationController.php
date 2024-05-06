<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use App\Services\ResponseService;
use App\Models\GoogleCampaign;
use App\Models\GoogleAdgroup;
use App\Models\GoogleRuleAutomation;
use App\Models\GoogleAutomationLog;
use App\Models\Enums\GoogleRuleTypeEnum;
use App\Models\Services\GoogleRuleAutomationService;
use App\Http\Resources\GoogleRuleAutomationResource;
use App\Http\Resources\GoogleAutomationLogResource;

class GoogleRuleAutomationController extends Controller
{
    public static function apiRoutes()
    {
        Route::get('google-automation', [GoogleRuleAutomationController::class, 'collection']);
        Route::get('google-automation/campaigns', [GoogleRuleAutomationController::class, 'getCampaigns']);
        Route::get('google-automation/adgroups', [GoogleRuleAutomationController::class, 'getAdgroups']);
        Route::post('google-automation', [GoogleRuleAutomationController::class, 'create']);
        Route::put('google-automation/{ggRuleAutomation}', [GoogleRuleAutomationController::class, 'update']);
        Route::get('google-automation/{ggRuleAutomation}', [GoogleRuleAutomationController::class, 'get']);
        Route::delete('google-automation/{ggRuleAutomation}', [GoogleRuleAutomationController::class, 'delete']);
        Route::get('google-automation/{ggRuleAutomation}/updateStatus', [GoogleRuleAutomationController::class, 'updateStatus']);
        Route::get('google-automation/{ggRuleAutomation}/run', [GoogleRuleAutomationController::class, 'run']);
        Route::get('google-automation/{ggRuleAutomation}/log', [GoogleRuleAutomationController::class, 'collectionLog']);
    }

    public function collection(Request $request)
    {
        $search = $request->input('search', null);

        $ggRuleAutomations = GoogleRuleAutomation::search(
            $search
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleRuleAutomationResource::collection($ggRuleAutomations);
    }

    public function getCampaigns(Request $request)
    {
        $customer_id = $request->input('customer_id', null);

        $campaigns = GoogleCampaign::search(
            null,
            null,
            $customer_id
        )->get(['id', 'title'])->toArray();

        return ResponseService::success('Success', $campaigns);
    }

    public function getAdgroups(Request $request)
    {
        $campaign_id = $request->input('campaign_id', null);

        $adgroups = GoogleAdgroup::search(
            null,
            $campaign_id
        )->get(['id', 'title'])->toArray();

        return ResponseService::success('Success', $adgroups);
    }

    public function create(Request $request)
    {
        $ggRuleAutomation = GoogleRuleAutomationService::create(
            $request->input('name'),
            $request->input('apply_to'),
            $request->input('apply_to_id'),
            $request->input('action'),
            $request->input('frequency'),
            $request->input('conditions')
        );

        if ($request['apply_to_ids'] != null)
            $ggRuleAutomation->Service()->syncApplys($request->input('apply_to_ids'));

        return ResponseService::successCreate(
            'Google rule automation was created',
            new GoogleRuleAutomationResource($ggRuleAutomation)
        );
    }

    public function update(GoogleRuleAutomation $ggRuleAutomation, Request $request)
    {
        $result = $ggRuleAutomation->Service()->update(
            $request->input('name'),
            $request->input('action'),
            $request->input('frequency'),
            $request->input('conditions')
        );

        if ($request['apply_to_ids'] != null)
            $ggRuleAutomation->Service()->syncApplys($request->input('apply_to_ids'));

        if (isset($result['error'])) {
            return ResponseService::serverError('Google rule automation was not updated.');
        }

        return ResponseService::successCreate('Google rule automation was updated.', new GoogleRuleAutomationResource($ggRuleAutomation));
    }

    public function get(GoogleRuleAutomation $ggRuleAutomation)
    {
        return new GoogleRuleAutomationResource($ggRuleAutomation);
    }

    public function delete(GoogleRuleAutomation $ggRuleAutomation)
    {
        $ggRuleAutomation->Service()->delete();

        return ResponseService::successCreate('Rule was deleted successfully.');
    }

    public function run(GoogleRuleAutomation $ggRuleAutomation)
    {
        switch ($ggRuleAutomation->apply_to) {
            case GoogleRuleTypeEnum::CAMPAIGN:
                $ggRuleAutomation->Service()->processAutomationEx();
                break;
            case GoogleRuleTypeEnum::ADGROUP:
                $ggRuleAutomation->Service()->processAutomation();
                break;
            case GoogleRuleTypeEnum::AD:
                $ggRuleAutomation->Service()->processAutomationBulk();
                break;
            default:
                break;
        }

        return ResponseService::successCreate('Rule finished running.');
    }

    public function collectionLog(GoogleRuleAutomation $ggRuleAutomation, Request $request)
    {
        $search = $request->input('search', null);

        $ggAutomationLogs = GoogleAutomationLog::search(
            $search,
            $ggRuleAutomation->id
        )->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 10));

        return GoogleAutomationLogResource::collection($ggAutomationLogs);
    }

    public function updateStatus(GoogleRuleAutomation $ggRuleAutomation, Request $request)
    {
        $result = $ggRuleAutomation->Service()->updateStatus(
            $request->input('status')
        );

        if (isset($result['error'])) {
            return ResponseService::serverError('Google rule automation was not updated.');
        }

        return ResponseService::successCreate('Google rule automation was updated.', new GoogleRuleAutomationResource($ggRuleAutomation));
    }
}