<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FacebookAudienceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'audience_name' => $this->audience_name,
            'audience_description' => $this->audience_description,
            'audience_id' => $this->audience_id,
            'channel_id' => $this->channel_id,
            'audience_type' => $this->audience_type,
            'setup_details' => $this->setup_details,
            'channel' => $this->channel
        ];
    }
}
