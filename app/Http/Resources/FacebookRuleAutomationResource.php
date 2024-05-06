<?php

namespace App\Http\Resources;

use App\Models\FacebookRuleAutomation;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FacebookRuleAutomationResource.
 *
 * @property FacebookRuleAutomation $resource
 */
class FacebookRuleAutomationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array|Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'target' => $this->resource->target,
            'target_label' => $this->resource->target->getLabel(),
            'action' => $this->resource->action,
            'action_label' => $this->resource->action->getLabel(),
            'hours' => $this->resource->hours,
            'user_id' => $this->resource->user_id,
            'account_id' => $this->resource->account_id,
            'conditions' => FacebookRuleConditionResource::collection($this->resource->ruleConditions),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at
        ];
    }
}
