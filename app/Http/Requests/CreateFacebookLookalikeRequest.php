<?php

namespace App\Http\Requests;

use App\Models\Enums\FacebookAudienceTypeEnum;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateFacebookLookalikeRequest extends FormRequest
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
            'audience_description' => 'nullable|sometimes',
            'audience_type' => Rule::in(FacebookAudienceTypeEnum::members()),
            'facebook_audience_id' => 'required|numeric',
            'starting_size' => 'required|numeric',
            'ending_size' => 'required|numeric|gt:starting_size',
            'country' => 'required',
            'audience_id' => 'sometimes',
            'ad_account' => 'sometimes'
        ];
    }
}
