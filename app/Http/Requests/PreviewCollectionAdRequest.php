<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewCollectionAdRequest extends FormRequest
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
            'ads_number' => 'required|numeric',
            'add_images' => 'required|array|min:1',
            'add_title' => 'required|array|min:1',
            'add_headline' => 'required|array|min:1',
            'add_text' => 'required|array|min:1',
            'add_call_to_action' => 'required|array|min:1',
            'add_url' => 'required|array|min:1',
        ];
    }
}
