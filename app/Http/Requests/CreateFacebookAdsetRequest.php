<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignInAppStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFacebookAdsetRequest extends FormRequest
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
            '*.campaign_id' => 'required|exists:facebook_campaigns,id',
            '*.title' => 'required',
            '*.adset' => 'required',
            '*.status' => ['required', Rule::in(CampaignInAppStatusEnum::members())]
        ];
    }
}
