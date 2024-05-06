<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FacebookRuleAutomationRequest;
use App\Http\Resources\FacebookRuleAutomationResource;
use App\Models\Enums\FbRuleActionEnum;
use App\Models\Enums\FbRuleTargetEnum;
use App\Models\FacebookRuleAutomation;
use App\Models\Services\FacebookRuleAutomationService;
use App\Services\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class FacebookRuleAutomationController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('rule-automations', [FacebookRuleAutomationController::class, 'create']);
        Route::put('rule-automations/{ruleAutomation}', [FacebookRuleAutomationController::class, 'update']);
        Route::delete('rule-automations/{ruleAutomation}', [FacebookRuleAutomationController::class, 'delete']);
        Route::get('rule-automations/{ruleAutomation}', [FacebookRuleAutomationController::class, 'get']);
        Route::get('rule-automations', [FacebookRuleAutomationController::class, 'collection']);
    }

    public function collection(Request $request)
    {
        $search = $request->input('search', null);

        return FacebookRuleAutomationResource::collection(
            FacebookRuleAutomation::search($search)
                ->latest()
                ->paginate($request->input('per_page', 10))
        );
    }

    public function get(FacebookRuleAutomation $ruleAutomation)
    {
        return new FacebookRuleAutomationResource($ruleAutomation);
    }

    public function create(FacebookRuleAutomationRequest $request)
    {
        $fbRuleAutomation = FacebookRuleAutomationService::create(
            $request->validated()['name'],
            FbRuleTargetEnum::memberByValue($request->validated()['target']),
            FbRuleActionEnum::memberByValue($request->validated()['action']),
            $request->validated()['hours'],
            $request->validated()['conditions']
        );

        return ResponseService::successCreate(
            'Facebook rule automation was created',
            new FacebookRuleAutomationResource($fbRuleAutomation)
        );
    }

    public function update(FacebookRuleAutomationRequest $request, FacebookRuleAutomation $ruleAutomation)
    {
        $fbRuleAutomation = $ruleAutomation->Service()->update(
            $request->validated()['name'],
            FbRuleTargetEnum::memberByValue($request->validated()['target']),
            FbRuleActionEnum::memberByValue($request->validated()['action']),
            $request->validated()['hours'],
            $request->validated()['conditions']
        );

        return ResponseService::success(
            'Facebook rule automation was updated',
            new FacebookRuleAutomationResource($fbRuleAutomation)
        );
    }

    public function delete(FacebookRuleAutomation $ruleAutomation)
    {
        $ruleAutomation->Service()->delete();

        return ResponseService::success('Facebook rule automation was archived');
    }
}
