<?php

namespace App\Http\Requests;

use App\Models\Enums\RoleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserSuperAdminRequest extends FormRequest
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
            'account_id' => 'required|integer',
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email:rfc|unique:App\Models\User,email',
            'password' => 'required|confirmed',
            'role_id' => ['required', Rule::in(RoleTypeEnum::members())],
            'is_owner' => 'required',
        ];
    }
}
