<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;
use App\Models\SubDomain;

class DomainResource extends JsonResource
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
            'server' => $this->server,
            'domain' => $this->domain,
            'subdomains' => ($this->subdomains) ? $this->subdomains->map(function (SubDomain $subdomain) {
                return [
                    'name' => $subdomain->name
                ];
            }) : [],
            'username' => $this->username,
            'nameserver1' => $this->nameserver1,
            'nameserver2' => $this->nameserver2,
            'status' => $this->status,
            'status_label' => $this->status->getLabel(),
            'ipaddress' => $this->ipaddress,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'user' => new UserResourceLite($this->user)
        ];
    }
}
