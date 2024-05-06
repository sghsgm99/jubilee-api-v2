<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Enums\BlackListStatusEnum;
use App\Models\Enums\BlackListTypeEnum;

class UpdateBlackListRequest extends FormRequest
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
            'name' => 'required',
            'type' => ['required', Rule::in(BlackListTypeEnum::members())],
            'status' => ['required', Rule::in(BlackListStatusEnum::members())],
        ];
    }
}