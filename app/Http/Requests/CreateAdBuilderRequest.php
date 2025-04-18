<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAdBuilderRequest extends FormRequest
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
            'url' => 'required',
            'gjs_components' => 'nullable',
            'gjs_style' => 'nullable',
            'gjs_html' => 'nullable',
            'gjs_css' => 'nullable'
        ];

    }
}