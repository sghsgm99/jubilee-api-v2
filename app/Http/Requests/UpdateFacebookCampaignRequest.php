<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignInAppStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacebookCampaignRequest extends FormRequest
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
            'status' => ['required', Rule::in(CampaignInAppStatusEnum::members())]
        ];
    }
}
