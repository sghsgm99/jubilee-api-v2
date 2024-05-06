<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdBuilderRequest extends FormRequest
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
            'gjs_components' => 'nullable',
            'gjs_style' => 'nullable',
            'gjs_html' => 'required',
            'gjs_css' => 'nullable'
        ];

    }
}