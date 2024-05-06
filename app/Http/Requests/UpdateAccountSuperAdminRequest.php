<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountSuperAdminRequest extends FormRequest
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
            'company_name' => 'required|string',
            'facebook_app_id' => 'string|nullable',
            'facebook_app_secret' => 'string|nullable',
            'facebook_business_manager_id' => 'string|nullable',
            'facebook_access_token' => 'string|nullable',
            'facebook_line_of_credit_id' => 'string|nullable',
            'facebook_primary_page_id' => 'string|nullable',
            'report_token' => 'string|nullable',
            'view_id' => 'string|nullable',
            'analytic_file' => 'nullable|file|mimes:json',
            'analytic_script' => 'string|nullable',
        ];
    }
}
