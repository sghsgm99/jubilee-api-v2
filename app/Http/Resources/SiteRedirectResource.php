<?php

namespace App\Http\Resources;

use App\Models\Site;
use App\Models\SiteRedirect;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SiteRedirectResource.
 *
 * @property SiteRedirect $resource
 */
class SiteRedirectResource extends JsonResource
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
            'site_id' => $this->resource->site_id,
            'source' => $this->resource->source,
            'destination' => $this->resource->destination,
            'code' => $this->resource->code,
        ];
    }
}
