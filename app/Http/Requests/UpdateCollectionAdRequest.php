<?php

namespace App\Http\Requests;

use App\Models\Enums\CollectionAdStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollectionAdRequest extends FormRequest
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
            'channel_id' => 'required|numeric',
            'ad_account_id' => 'required',
            'campaign_id' => 'required|numeric',
            'adset_id' => 'required|numeric',
            'ads_number' => 'required|numeric',
            'add_images' => 'sometimes',
            'add_title' => 'sometimes',
            'add_headline' => 'sometimes',
            'add_text' => 'sometimes',
            'add_call_to_action' => 'sometimes',
            'add_url' => 'sometimes',
            'status' => ['required', Rule::in(CollectionAdStatusEnum::members())],
        ];
    }
}
