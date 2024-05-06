<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SiteOnlyResource extends JsonResource
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
            'name' => $this->name,
            'url' => $this->url,
            'api_key' => $this->api_key,
            'api_callback' => $this->api_callback,
            'api_permissions' => $this->api_permissions,
            'client_key' => $this->client_key,
            'client_secret_key' => $this->client_secret_key,
            'description' => $this->description,
            'platform' => $this->platform,
            'platform_label' => $this->platform->getLabel(),
            'status' => $this->status,
            'status_label' => $this->status->getLabel(),
            'creator' => $this->user->full_name ?? null,
            'account_id' => $this->account_id,
            'favicon' => $this->favicon ? new SiteFaviconResource($this->favicon) : null,
            'logo' => $this->logo ? new SiteLogoResource($this->logo) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
