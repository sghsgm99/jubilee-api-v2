<?php

namespace App\Http\Resources;

use App\Models\Site;
use Illuminate\Http\Resources\Json\JsonResource;

class OcodesResource extends JsonResource
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
            'ocode' => $this->ocode,
            'client_id' => $this->client_id,
            'site' => $this->site,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
