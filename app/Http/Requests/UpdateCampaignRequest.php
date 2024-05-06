<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignStatusEnum;
use App\Models\Enums\FacebookCampaignStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCampaignRequest extends FormRequest
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
            'channel_api_preferences' => 'sometimes',
            'channel_id' => 'required|exists:channels,id',
            'article_id' => 'required|exists:articles,id',
            'site_id' => 'required|exists:sites,id',
            'ad_account' => 'sometimes|nullable',
            'status' => ['required', Rule::in(CampaignStatusEnum::members())],
            'data' => 'required'
        ];

    }
}