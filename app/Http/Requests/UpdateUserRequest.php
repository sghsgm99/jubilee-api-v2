<?php

namespace App\Http\Requests;

use App\Models\Enums\RoleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
        $members = RoleTypeEnum::members();
        Arr::forget($members, RoleTypeEnum::ROOT()->getLabel());

        return [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|email:rfc',
            'is_owner' => 'required|boolean',
            'role_id' => ['required', Rule::in($members)],
            'password' => 'nullable|confirmed',
            'is_active' => 'required|boolean',
        ];

    }
}
