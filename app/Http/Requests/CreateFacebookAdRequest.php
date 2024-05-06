<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Enums\FacebookCampaignStatusEnum;

class CreateFacebookAdRequest extends FormRequest
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
            'adset_id' => 'required',
            'article_id' => 'required|integer|exists:articles,id',
            'status' => Rule::in(FacebookCampaignStatusEnum::members())
        ];

    }
}
