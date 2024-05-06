<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadChannelImage extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'images' => ['array'],
            'images.*' => ['required', 'image', 'max:5000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
        ];
    }
}
