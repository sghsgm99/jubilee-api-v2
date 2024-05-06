<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCampaignObjectiveEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFacebookCampaignRequest extends FormRequest
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
            'channel_id' => 'required|exists:channels,id',
            'ad_account_id' => 'required',
            'title' => 'required',
            'description' => 'sometimes',
            'objective' => ['required', Rule::in(FacebookCampaignObjectiveEnum::members())],
            'status' => ['required', Rule::in(CampaignInAppStatusEnum::members())]
        ];
    }
}
