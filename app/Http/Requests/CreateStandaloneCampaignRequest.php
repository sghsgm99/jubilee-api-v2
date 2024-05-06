<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateStandaloneCampaignRequest extends FormRequest
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
            'title' => 'required',
            'description' => 'sometimes',
            'channel_api_preferences' => 'nullable',
            'channel_id' => 'required|exists:channels,id',
            'site_id' => 'required|exists:sites,id',
            'status' => ['required', Rule::in(CampaignStatusEnum::members())],
            'ad_image' => ['image', 'max:5000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
            'primary_text' => 'required',
            'headline' => 'required',
            'ad_account' => 'sometimes|nullable',
            'ad_description' => ['present', 'nullable'],
            'display_link' => ['present', 'nullable', 'url'],
            'call_to_action' => ['required', Rule::in(FacebookCallToActionEnum::members())],
            'data' => 'required'
        ];
    }
}
