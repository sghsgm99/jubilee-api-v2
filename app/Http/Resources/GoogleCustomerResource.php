<?php

namespace App\Http\Resources;

use App\Models\GoogleCustomer;
use Illuminate\Http\Resources\Json\JsonResource;

class GoogleCustomerResource extends JsonResource
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
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'customer_id' => $this->resource->customer_id,
            'google_account' => $this->resource->google_account,
            'status' => $this->resource->status,
            'status_label' => $this->resource->status->getLabel(),
            'author_id' => $this->resource->user->id,
            'author_name' => $this->resource->user->full_name,
            'created_at' => $this->resource->created_at,
            'deleted_at' => $this->resource->deleted_at
        ];
    }
}
