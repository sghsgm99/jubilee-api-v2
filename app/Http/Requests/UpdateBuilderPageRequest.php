<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuilderPageRequest extends FormRequest
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
        $slug = $this->input('slug');

        return [
            'title' => ['required', 'string'],
            'slug' => [
                'required',
                'string',
                Rule::unique('builder_pages')->where(function ($query) use ($slug) {
                    return $query->where('slug', $slug)
                        ->where('builder_site_id', $this->builderPage->builder_site_id)
                        ->where('id', '!=', $this->builderPage->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'html' => ['present', 'string', 'nullable'],
            'styling' => ['present', 'string', 'nullable'],
            'seo' => ['present', 'string', 'nullable'],
        ];
    }
}
