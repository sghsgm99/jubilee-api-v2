<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Models\FacebookAdAccount;
use App\Models\CollectionGroup;

class CollectionResource extends JsonResource
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
            'status' => $this->status,
            'status_label' => $this->status->getLabel(),
            'groups' => ($this->groups) ? $this->groups->map(function (CollectionGroup $group) {
                return [
                    'name' => $group->name
                ];
            }) : [],
            'collection_group_id' => $this->collection_group_id,
            'collection_group_name' =>  $this->collection_group_id ? $this->groups->find($this->collection_group_id)->name : null,
            'account_id' => $this->account_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'user_id' => $this->user->id ?? null,
            'author_name' => ($this->user) ? $this->user->fullname : 'No Author'
        ];
    }
}
