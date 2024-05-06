<?php

namespace App\Http\Resources;

use App\Models\BingReport;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class BingReportResource.
 *
 * @property BingReport $resource
 */
class BingReportResource extends JsonResource
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
            'job_id' => $this->resource->job_id,
            'job_id_string' => $this->resource->job_id_string,
            'name' => $this->resource->name,
            'status' => $this->resource->status,
            'download_url' => $this->resource->download_url,
            'data' => $this->resource->data,
            'reported_at' => $this->resource->reported_at,
            'created_at' => $this->resource->created_at,
        ];
    }
}
