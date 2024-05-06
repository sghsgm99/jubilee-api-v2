<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Enums\RuleSetTypeEnum;

class CreateRuleSetRequest extends FormRequest
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
            'advertiser' => 'required',
            'traffic_per' => 'required',
            'type' => ['required', Rule::in(RuleSetTypeEnum::members())],
        ];
    }
}