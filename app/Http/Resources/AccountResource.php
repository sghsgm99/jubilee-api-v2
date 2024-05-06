<?php

namespace App\Http\Resources;

use App\Models\Account;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AccountResource.
 *
 * @property Account $resource
 */
class AccountResource extends JsonResource
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
            'company_name' => $this->resource->company_name,
            'facebook_app_id' => $this->resource->facebook_app_id,
            'facebook_app_secret' => $this->resource->facebook_app_secret,
            'facebook_business_manager_id' => $this->resource->facebook_business_manager_id,
            'facebook_access_token' => $this->resource->facebook_access_token,
            'facebook_line_of_credit_id' => $this->resource->facebook_line_of_credit_id,
            'facebook_primary_page_id' => $this->resource->facebook_primary_page_id,
            'view_id' => $this->resource->view_id,
            'analytic_file' => $this->resource->analytic_file,
            'analytic_script' => $this->resource->analytic_script,
            'report_token' => $this->resource->report_token,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at
        ];
    }
}
