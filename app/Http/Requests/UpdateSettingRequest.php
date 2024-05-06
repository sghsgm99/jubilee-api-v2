<?php

namespace App\Http\Requests;

use App\Models\Enums\RoleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingRequest extends FormRequest
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
            'account.company_name' => 'required_if:user.is_owner,'.true,
            'user.first_name' => 'required',
            'user.last_name' => 'required',
            'user.email' => 'required|email:rfc',
            'user.is_owner' => 'required|boolean',
            'user.role_id' => ['required', Rule::in(RoleTypeEnum::members())],
            'user.password' => 'nullable|confirmed'
        ];
    }
}
