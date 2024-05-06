<?php

namespace App\Http\Resources;

use App\Models\FacebookCampaign;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class FacebookCampaignResource.
 *
 * @property FacebookCampaign $resource
 */
class FacebookCampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $ruleAutomation = null;
        if ($this->resource->fbRuleAutomation) {
            $ruleAutomation = [
                'id' => $this->resource->fbRuleAutomation->id,
                'name' => $this->resource->fbRuleAutomation->name,
            ];
        }

        return [
            'id' => $this->resource->id,
            'fb_campaign_id' => $this->resource->fb_campaign_id,
            'ad_account_id' => $this->resource->ad_account_id,
            'title' => $this->resource->title,
            'description' => $this->resource->description,
            'objective' => $this->resource->objective,
            'objective_label' => $this->resource->objective->getLabel(),
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'fb_status' => $this->resource->fb_status,
            'fb_status_label' => $this->resource->fb_status->getLabel(),
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'channel' => $this->resource->channel,
            'channel_facebook' => $this->resource->channel->channelFacebook ?? null,
            'rule_automation' => $ruleAutomation,
            'tags' => CampaignTagResource::collection($this->resource->tags),
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at,
            'errored_at' => $this->resource->errored_at,
            'error_message' => $this->resource->error_message
        ];
    }
}
