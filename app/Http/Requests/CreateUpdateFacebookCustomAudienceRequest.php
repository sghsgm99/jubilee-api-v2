<?php

namespace App\Http\Requests;

use App\Models\Enums\FacebookAudienceTypeEnum;
use App\Models\Enums\FacebookPageEventFilterValueEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateUpdateFacebookCustomAudienceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'audience_name' => 'required',
            'audience_description' => 'sometimes|nullable',
            'event_source_id' => 'required',
            'retention_days' => 'required|integer',
            'event_filter_value' => Rule::in(FacebookPageEventFilterValueEnum::keys()),
            'audience_type' => Rule::in(FacebookAudienceTypeEnum::members()),
            'audience_id' => 'sometimes',
            'ad_account' => 'sometimes'
        ];
    }
}
