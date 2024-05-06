<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class SuperAdminUserResource extends JsonResource
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
            'first_name' => $this->resource->first_name,
            'last_name' => $this->resource->last_name,
            'full_name' => $this->resource->full_name,
            'email' => $this->resource->email,
            'account' => new AccountResource($this->account),
            'is_owner' => $this->resource->is_owner,
            'is_active' => $this->resource->is_active,
            'has_analytics_setup' => $this->resource->account->has_analytics_setup,
            'role_id' => $this->resource->role_id,
            'role_label' => $this->resource->role_id->getLabel(),
            'role_setup' => $this->resource->roleSetup->setup,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
            'deleted_at' => $this->resource->deleted_at,
        ];
    }
}
