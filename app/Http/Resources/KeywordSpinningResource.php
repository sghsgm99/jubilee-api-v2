<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class KeywordSpinningResource extends JsonResource
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
            'keyword' => $this->keyword,
            'advertiser' => $this->advertiser,
            'url' => $this->url,
            'category' => $this->category,
            'clicks' => $this->clicks,
            'impr' => $this->impr,
            'ctr' => $this->ctr,
            'cpc' => $this->cpc,
            'conversion' => $this->conversion,
        ];
    }
}
