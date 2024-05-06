<?php

namespace App\Http\Requests;

use App\Models\Enums\SiteStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSiteThemeRequest extends FormRequest
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
            'title' => 'required|string|unique:site_themes',
            'handle' => 'required|string|unique:site_themes',
            'status' => 'required|boolean',
            'description' => 'nullable'
        ];
    }
}
