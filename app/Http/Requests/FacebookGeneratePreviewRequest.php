<?php

namespace App\Http\Requests;

use App\Models\Enums\FacebookCallToActionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FacebookGeneratePreviewRequest extends FormRequest
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
            'channel_id' => 'required',
            'ad_image' => ['required'],
            'primary_text' => 'required',
            'headline' => 'required',
            'ad_account' => 'sometimes|nullable',
            'ad_description' => ['present', 'required'],
            'display_link' => ['present', 'required', 'url'],
            'call_to_action' => ['required', Rule::in(FacebookCallToActionEnum::members())],
        ];
    }
}
