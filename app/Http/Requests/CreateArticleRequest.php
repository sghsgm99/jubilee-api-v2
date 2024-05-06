<?php

namespace App\Http\Requests;

use App\Models\Enums\ArticleStatusEnum;
use App\Models\Enums\ArticleTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateArticleRequest extends FormRequest
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
            'user_id' => 'present|nullable',
            'title' => 'required',
            'content' => 'present|nullable',
            'toggle_length' => 'present|numeric|nullable',
            'status' => ['required', Rule::in(ArticleStatusEnum::members())],
            'type' => ['required', Rule::in(ArticleTypeEnum::members())],
            'images' => ['array'],
            'images.*' => ['image', 'max:5000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
        ];

    }
}
