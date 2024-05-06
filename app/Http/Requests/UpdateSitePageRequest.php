<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSitePageRequest extends FormRequest
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
                Rule::unique('site_pages')->where(function ($query) use ($slug) {
                    return $query->where('slug', $slug)
                        ->where('site_id', $this->sitePage->site_id)
                        ->where('id', '!=', $this->sitePage->id)
                        ->whereNull('deleted_at');
                }),
            ],
            'content' => ['required', 'string']
        ];
    }
}
