<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class RuleSetResource extends JsonResource
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
            'account_id' => $this->account_id,
            'name' => $this->name,
            'type' => $this->type,
            'advertiser' => $this->advertiser,
            'traffic_per' => $this->traffic_per,
            'turn_state' => $this->turn_state == 0 ? false : true,
            'schedule' => $this->schedule,
            'schedule_sel' => $this->schedule['schedule'],
            'button' => $this->button,
            'creator' => $this->user->full_name ?? null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'user' => new UserResource($this->user)
        ];
    }
}
