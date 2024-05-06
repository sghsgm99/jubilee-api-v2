<?php

namespace App\Http\Resources;

use App\Models\FacebookRuleAutomationCondition;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FacebookRuleConditionResource.
 *
 * @property FacebookRuleAutomationCondition $resource
 */
class FacebookRuleConditionResource extends JsonResource
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
            'facebook_rule_automation_id' => $this->resource->facebook_rule_automation_id,
            'logical_operator' => $this->resource->logical_operator,
            'logical_operator_label' => $this->resource->logical_operator->getLabel(),
            'target' => $this->resource->target,
            'target_label' => $this->resource->target->getLabel(),
            'conditions' => $this->resource->conditions,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at
        ];
    }
}
