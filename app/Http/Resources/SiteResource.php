<?php

namespace App\Http\Resources;

use App\Models\Site;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SiteResourceProvisioning.
 *
 * @property Site $resource
 */
class SiteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return[
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'url' => $this->resource->url,
            'api_callback' => $this->resource->api_callback,
            'api_permissions' => $this->resource->api_permissions,
            'client_key' => $this->resource->client_key,
            'client_secret_key' => $this->resource->client_secret_key,
            'description' => $this->resource->description,
            'platform' => $this->resource->platform,
            'platform_label' => $this->resource->platform->getLabel(),
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'creator' => $this->resource->user->full_name ?? null,
            'account_id' => $this->resource->account_id,
            'setting' => $this->resource->settings,
            'favicon' => $this->resource->favicon ? new SiteFaviconResource($this->resource->favicon) : null,
            'logo' => $this->resource->logo ? new SiteLogoResource($this->resource->logo) : null,
            'view_id' => $this->resource->view_id,
            'analytic_file' => $this->resource->analytic_file,
            'analytic_script' => $this->resource->analytic_script,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,
            'user' => new UserResourceLite($this->resource->user)
        ];
    }
}
