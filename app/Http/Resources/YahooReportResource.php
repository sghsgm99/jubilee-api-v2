<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class YahooReportResource extends JsonResource
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
            'id' => $this->id,
            'account_id' => $this->account_id,
            'type' => $this->type,
            'data' => $this->data,
            'reported_at' => $this->reported_at,
            'created_at' => $this->created_at,
        ];
    }
}
