<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Enums\GoogleRuleTypeEnum;

class GoogleAutomationLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        switch ($this->resource->ggRuleAutomation->apply_to) {
            case GoogleRuleTypeEnum::CAMPAIGN:
                $owner = $this->resource->ggRuleAutomation->customer->name;
                break;
            case GoogleRuleTypeEnum::ADGROUP:
            case GoogleRuleTypeEnum::AD:
                $owner = $this->resource->ggRuleAutomation->adgroup->campaign->customer->name;
                break;
            default: 
                $owner = '';
                break;
        }

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->ggRuleAutomation->name,
            'apply_to' => $this->resource->ggRuleAutomation->apply_to,
            'owner' => $owner,
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'title' => $this->resource->description['title'],
            'action' => $this->resource->description['action'],
            'conditions' => $this->resource->description['conditions'],
            'changes' => $this->resource->changes,
            'account_id' => $this->resource->account_id,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at
        ];
    }
}
