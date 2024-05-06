<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBuilderPageRequest extends FormRequest
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
        $builder_site_id = $this->input('builder_site_id');
        $slug = $this->input('slug');

        return [
            'builder_site_id' => ['required', 'integer'],
            'title' => ['required', 'string'],
            'slug' => [
                'required',
                'string',
                Rule::unique('builder_pages')->where(function ($query) use ($builder_site_id, $slug) {
                    return $query->where('slug', $slug)
                        ->where('builder_site_id', $builder_site_id)
                        ->whereNull('deleted_at');
                }),
            ]
        ];
    }
}
