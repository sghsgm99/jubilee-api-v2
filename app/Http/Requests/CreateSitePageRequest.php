<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSitePageRequest extends FormRequest
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
        $site_id = $this->input('site_id');
        $slug = $this->input('slug');

        return [
            'site_id' => ['required', 'integer'],
            'title' => ['required', 'string'],
            'slug' => [
                'required',
                'string',
                Rule::unique('site_pages')->where(function ($query) use ($site_id, $slug) {
                    return $query->where('slug', $slug)
                        ->where('site_id', $site_id)
                        ->whereNull('deleted_at');
                }),
            ],
            'content' => ['required', 'string']
        ];
    }
}
