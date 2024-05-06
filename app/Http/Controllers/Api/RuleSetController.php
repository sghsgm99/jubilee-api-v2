<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\RuleSet;
use App\Services\ResponseService;
use App\Http\Resources\RuleSetResource;
use App\Models\Services\RuleSetService;
use App\Http\Requests\CreateRuleSetRequest;
use App\Http\Requests\UpdateRuleSetRequest;
use App\Models\Enums\RuleSetTypeEnum;

class RuleSetController extends Controller
{
    public static function apiRoutes()
    {
        Route::post('rulesets', [RuleSetController::class, 'create']);
        Route::get('rulesets', [RuleSetController::class, 'getCollection']);
        Route::get('rulesets/{ruleset}', [RuleSetController::class, 'get']);
        Route::put('rulesets/{ruleset}', [RuleSetController::class, 'update']);
        Route::delete('rulesets/{ruleset}', [RuleSetController::class, 'delete']);
    }

    public function create(CreateRuleSetRequest $request)
    {
        $new_schedule =
        [
            'schedule' => $request->schedule_sel,
            'frequency' => [
                'option'=> $request->frequency,
                'info' => [
                    [
                        'date' => $request->onetime_date,
                        'start_time' => $request->onetime_start,
                        'end_time' => $request->onetime_end,
                        'all_time' => $request->onetime_24hrs
                    ],
                    [
                        'start_time' => $request->daily_start,
                        'end_time' => $request->daily_end,
                        'all_time' => $request->daily_24hrs
                    ],
                    [
                        'weekly_sel' => $request->weekly_sel,
                        'start_time' => $request->weekly_start,
                        'end_time' => $request->weekly_end,
                        'all_time' => $request->weekly_24hrs
                    ],
                ],
            ],
            'duration' => [
                'start_date' => $request->duration_start,
                'end_date' => $request->duration_end,
                'no_end' => $request->duration_noend
            ]
        ];

        if (RuleSetTypeEnum::memberByValue($request->type)->value == RuleSetTypeEnum::BUTTONS) {
            $new_button = [
                'text' => $request->btn_text,
                'color' => [$request->static_color, $request->hover_color, $request->font_color],
                'style' => $request->btn_style,
                'size' => $request->btn_size
            ];
        } else {
            $new_button = null;
        }

        $ruleset = RuleSetService::create(
            Auth::user(),
            $request->validated()['name'],
            RuleSetTypeEnum::memberByValue($request->validated()['type']),
            $request->validated()['advertiser'],
            $request->validated()['traffic_per'],
            $request->turn_state,
            $new_schedule,
            $new_button
        );
        
        return ResponseService::successCreate('RuleSet was created.', new RuleSetResource($ruleset));
    }

    public function getCollection(Request $request)
    {
        $search = $request->input('search', null);
        $sort = $request->input('sort', null);
        $sort_type = $request->input('sort_type', 'asc');

        $rulesets = RuleSet::search($search, $sort, $sort_type)
            ->paginate($request->input('per_page', 10));

        return RuleSetResource::collection($rulesets);
    }

    public function get(RuleSet $ruleset)
    {
        return ResponseService::success('Success', new RuleSetResource($ruleset));
    }

    public function update(UpdateRuleSetRequest $request, RuleSet $ruleset)
    {
        $update_schedule =
        [
            'schedule' => $request->schedule_sel,
            'frequency' => [
                'option'=> $request->frequency,
                'info' => [
                    [
                        'date' => $request->onetime_date,
                        'start_time' => $request->onetime_start,
                        'end_time' => $request->onetime_end,
                        'all_time' => $request->onetime_24hrs
                    ],
                    [
                        'start_time' => $request->daily_start,
                        'end_time' => $request->daily_end,
                        'all_time' => $request->daily_24hrs
                    ],
                    [
                        'weekly_sel' => $request->weekly_sel,
                        'start_time' => $request->weekly_start,
                        'end_time' => $request->weekly_end,
                        'all_time' => $request->weekly_24hrs
                    ],
                ],
            ],
            'duration' => [
                'start_date' => $request->duration_start,
                'end_date' => $request->duration_end,
                'no_end' => $request->duration_noend
            ]
        ];

        if (RuleSetTypeEnum::memberByValue($request->type)->value == RuleSetTypeEnum::BUTTONS) {
            $update_button = [
                'text' => $request->btn_text,
                'color' => [$request->static_color, $request->hover_color, $request->font_color],
                'style' => $request->btn_style,
                'size' => $request->btn_size
            ];
        } else {
            $update_button = null;
        }

        $ruleset = $ruleset->Service()->update(
            $request->validated()['name'],
            RuleSetTypeEnum::memberByValue($request->validated()['type']),
            $request->validated()['advertiser'],
            $request->validated()['traffic_per'],
            $request->turn_state,
            $update_schedule,
            $update_button
        );

        if (isset($ruleset['error'])) {
            return ResponseService::serverError($ruleset['message']);
        }

        return ResponseService::successCreate('RuleSet was updated.', new RuleSetResource($ruleset));
    }

    public function delete(RuleSet $ruleset)
    {
        if (!$ruleset->Service()->delete()) {
            return ResponseService::serverError('RuleSet cannot be deleted');
        }

        return ResponseService::success('RuleSet was archived.');
    }
}
