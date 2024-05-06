<?php

namespace App\Http\Resources;
use App\Models\Domain;

use App\Models\SubDomain;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SubDomainResource.
 *
 * @property SubDomain $resource
 */
class SubDomainResource extends JsonResource
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
            'name' => $this->resource->name,
            'domain' => $this->resource->domains['domain'],
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
