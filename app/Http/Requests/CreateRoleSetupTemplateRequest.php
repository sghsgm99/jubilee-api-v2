<?php

namespace App\Http\Requests;

use App\Models\Enums\PageTypeEnum;
use App\Models\Enums\PermissionTypeEnum;
use Illuminate\Validation\Rule;
use App\Models\Enums\RoleTypeEnum;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoleSetupTemplateRequest extends FormRequest
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
            'role_id' => ['required', Rule::in(RoleTypeEnum::members())],
            'setup_name' => 'required|string',
            'setup' => 'required|array',
            'setup.*.page' => ['required', Rule::in(PageTypeEnum::members())],
            'setup.*.permission' => 'required|array',
            'setup.*.permission.*' => ['required', Rule::in(PermissionTypeEnum::members())]
            
        ];
    }
}