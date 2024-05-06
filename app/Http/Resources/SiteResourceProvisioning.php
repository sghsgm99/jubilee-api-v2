<?php

namespace App\Http\Resources;

use App\Models\Site;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class SiteResourceProvisioning.
 *
 * @property Site $resource
 */
class SiteResourceProvisioning extends JsonResource
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
            'host' => $this->resource->host,
            'ssh_username' => $this->resource->ssh_username,
            'ssh_password' => $this->resource->ssh_password,
            'path' => $this->resource->path,
            'api_jubilee_key' => $this->resource->api_jubilee_key
        ];
    }
}
