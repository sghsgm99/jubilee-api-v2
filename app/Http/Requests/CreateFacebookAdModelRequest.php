<?php

namespace App\Http\Requests;

use App\Models\Enums\CampaignInAppStatusEnum;
use App\Models\Enums\FacebookCallToActionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateFacebookAdModelRequest extends FormRequest
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
            '*.adset_id' => 'required|exists:facebook_adsets,id',
            '*.status' => ['required', Rule::in(CampaignInAppStatusEnum::members())],
            '*.title' => 'required',
        ];

        if ($this->has('0.article_id')) {
            $rules['*.article_id'] = 'required|exists:articles,id';
        } else {
            $rules = $rules + [
                '*.primary_text' => ['present', 'nullable'],
                '*.headline' => ['present', 'nullable'],
                '*.description' => ['present', 'nullable'],
                '*.display_link' => ['present', 'nullable', 'url'],
                '*.call_to_action' => ['present', 'nullable', Rule::in(FacebookCallToActionEnum::members())],
                '*.image' => ['image', 'max:2000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
            ];
        }

        return $rules;
    }
}
