<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateDefaultCustomAudienceRequest extends FormRequest
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
            'channel_id' => 'string|required',
            'name' => 'string|required',
            'id' => 'string|required',
            'type' => 'string|required',
            'filter_field' => 'string|required',
            'filter_operator' => 'string|required',
            'filter_value' => 'string|required',
        ];
    }
}
