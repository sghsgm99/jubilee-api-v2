<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateUpdateArticleScrollRequest extends FormRequest
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
            'title' => 'required',
            'description' => 'present|nullable',
            'image_description' => 'present|nullable',
            'image' => ['image', 'max:2000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
        ];
    }
}
