<?php

namespace App\Http\Resources;

use App\Models\Enums\FacebookAudienceDeliveryStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class FacebookAudienceCollectionResource extends JsonResource
{
    public function __construct($resource)
    {
        self::withoutWrapping();

        return parent::__construct($resource);
    }
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this['id'],
            'name' => $this['name'],
            'description' => $this['description'],
            'audience_id' => $this['id'],
            'rule' => $this['rule'] ?? null,
            'subtype' => $this['subtype'],
            'audience_type' => $this['audience_type'],
            'time_created' => $this['time_created'],
            'time_updated' => $this['time_updated'],
            'time_content_updated' => $this['time_content_updated'],
            'delivery_status' => $this['delivery_status'],
            'delivery_status_label' => isset($this['delivery_status']['code']) ? FacebookAudienceDeliveryStatusEnum::memberByValue($this['delivery_status']['code'])->key : null
        ];
    }
}
