<?php

namespace App\Http\Resources;

use App\Models\CampaignTag;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class CampaignTagResource.
 *
 * @property CampaignTag $resource
 */
class CampaignTagResource extends JsonResource
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
            'id' => $this->resource->id,
            'label' => $this->resource->label,
            'color' => $this->resource->color
        ];
    }
}
