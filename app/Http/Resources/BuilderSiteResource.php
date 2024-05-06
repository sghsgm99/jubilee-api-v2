<?php

namespace App\Http\Resources;

use App\Models\BuilderSite;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BuilderSiteResource.
 *
 * @property BuilderSite $resource
 */
class BuilderSiteResource extends JsonResource
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
            'domain' => $this->resource->domain,
            'seo' => $this->resource->seo,
            'api_builder_key' => $this->resource->api_builder_key,
            'host' => $this->resource->host,
            'ssh_username' => $this->resource->ssh_username,
            'ssh_password' => $this->resource->ssh_password,
            'path' => $this->resource->path,
            'preview_link' => $this->resource->preview_link,
            'logo' => $this->resource->logo ?? null,
            'favicon' => $this->resource->favicon ?? null,
            'pages' => BuilderPageResource::collection($this->resource->pages),
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
