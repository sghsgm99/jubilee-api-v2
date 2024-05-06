<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MROASResource extends JsonResource
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
            'cid' => $this->cid,
            'intl' => $this->intl,
            'keyword' => $this->keyword,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}
