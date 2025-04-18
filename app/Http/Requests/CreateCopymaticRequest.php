<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCopymaticRequest extends FormRequest
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
            'url' => 'required|string',
            'token' => 'required|string',
            'model' => 'required|string',
            'tone' => 'required|string',
            'language' => 'required|string',
            'creativity' => 'required|string',
            'content' => 'required|string',
        ];
    }
}
