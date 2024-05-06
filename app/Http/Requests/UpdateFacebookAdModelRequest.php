<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFacebookAdModelRequest extends FormRequest
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
        $rules = [
            'status' => ['required', Rule::in(CampaignInAppStatusEnum::members())],
        ];

        if ($this->has('article_id')) {
            $rules['article_id'] = 'required|exists:articles,id';
        } else {
            $rules = $rules + [
                    'title' => 'required',
                    'primary_text' => 'required',
                    'headline' => 'required',
                    'description' => ['present', 'nullable'],
                    'display_link' => ['present', 'nullable', 'url'],
                    'call_to_action' => ['required', Rule::in(FacebookCallToActionEnum::members())],
                    'image' => ['image', 'max:2000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
                ];
        }

        return $rules;
    }
}
