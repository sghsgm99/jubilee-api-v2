<?php

namespace App\Http\Resources;

use App\Models\Enums\FacebookAdAccountStatusEnum;
use Illuminate\Http\Resources\Json\JsonResource;

class FacebookAdAccountResource extends JsonResource
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
            'name' => $this['name'],
            'account_id' => $this['account_id'],
            'act_account_id' => $this['id'],
            'funding_source_id' => $this['funding_source'] ?? null,
            'account_status' => $this['account_status'],
            'account_status_label' => FacebookAdAccountStatusEnum::memberByValue($this['account_status'])->getLabel(),
        ];
    }
}
