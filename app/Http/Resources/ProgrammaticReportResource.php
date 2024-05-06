<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProgrammaticReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $response = [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'data' => $this->data,
            'reported_at' => $this->reported_at,
            'created_at' => $this->created_at,
        ];
        $response['data']['utm_campaign'] = $response['data']['utm_campaign'] == "{{campaign.name})" ? "unknown" : $response['data']['utm_campaign'];

        return $response;
    }
}
