<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\GoogleRuleAutomation;
use App\Models\GoogleCampaign;
use App\Models\GoogleAutomationLog;
use App\Models\Enums\GoogleRuleTypeEnum;

class GoogleRuleAutomationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        switch ($this->resource->apply_to) {
            case GoogleRuleTypeEnum::CAMPAIGN:
                $apply_to_obj = $this->resource->customer;
                $owner = $apply_to_obj->name;
                break;
            case GoogleRuleTypeEnum::ADGROUP:
                $apply_to_obj = $this->resource->adgroup;
                $owner = $apply_to_obj->campaign->customer->name;
                break;
            case GoogleRuleTypeEnum::AD:
                $apply_to_obj = $this->resource->adgroup;
                $owner = $apply_to_obj->campaign->customer->name;
                break;
            default: 
                $apply_to_obj = '';
                $owner = '';
                break;
        }

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'apply_to' => $this->resource->apply_to,
            'apply_to_obj' => $apply_to_obj,
            'applys' => ($this->resource->applys) ? $this->resource->applys->map(function (GoogleCampaign $campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title
                ];
            }) : [],
            'last_run' => GoogleAutomationLog::where('google_rule_automation_id', $this->resource->id)->latest('created_at')->first('created_at'),
            'owner' => $owner,
            'action' => $this->resource->action,
            'frequency' => $this->resource->frequency,
            'conditions' => $this->resource->ruleConditions,
            'status' => $this->resource->status,
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'account_id' => $this->resource->account_id,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at
        ];
    }
}
