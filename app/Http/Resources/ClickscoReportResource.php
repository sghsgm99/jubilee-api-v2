<?php

namespace App\Http\Resources;

use App\Models\ClickscoReport;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ClickscoReportResource.
 *
 * @property ClickscoReport $resource
 */
class ClickscoReportResource extends JsonResource
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
            'account_id' => $this->resource->account_id,
            'name' => $this->resource->name,
            'data' => $this->resource->data,
            'reported_at' => $this->resource->reported_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
