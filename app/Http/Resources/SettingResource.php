<?php

namespace App\Http\Resources;

use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'is_dark_mode' => $this->is_dark_mode,
            'account' => [
                'id' => $this->account->id,
                'company_name' => $this->account->company_name
            ],
            'user' => new UserResource($this->user),
        ];
    }
}
