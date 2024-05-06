<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
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
            'company_name' => 'required',
            'facebook_app_id' => 'string|sometimes|nullable',
            'facebook_app_secret' => 'string|sometimes|nullable',
            'facebook_business_manager_id' => 'string|sometimes|nullable',
            'facebook_access_token' => 'string|sometimes|nullable',
            'facebook_line_of_credit_id' => 'string|sometimes|nullable',
            'facebook_primary_page_id' => 'string|sometimes|nullable',
        ];
    }
}
