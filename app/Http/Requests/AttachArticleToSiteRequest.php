<?php

namespace App\Http\Requests;

use App\Models\Enums\ArticleStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachArticleToSiteRequest extends FormRequest
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
            'site_id' => 'required',
            'category_ids' => 'nullable|present|array',
            'category_ids.*' => 'integer',
            'tag_ids' => 'nullable|present|array',
            'tag_ids.*' => 'integer',
            'menu_ids' => 'nullable|present|array',
            'menu_ids.*' => 'integer',
            'status' => ['required', Rule::in(ArticleStatusEnum::members())]
        ];
    }
}
