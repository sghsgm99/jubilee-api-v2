<?php

namespace App\Http\Requests;

use App\Models\Enums\CollectionAdStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCollectionAdRequest extends FormRequest
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
            'collection_id' => 'required|numeric',
            'channel_id' => 'required|numeric',
            'ad_account_id' => 'required',
            'campaign_id' => 'required|numeric',
            'adset_id' => 'required|numeric',
            'group_id' => 'required|numeric',
            'ads_number' => 'required|numeric',
            'add_images' => 'required_if:status,'.CollectionAdStatusEnum::PUBLISHED.'|required_if:array,min:1',
            'add_images' => 'required_if:status,'.CollectionAdStatusEnum::PUBLISHED.'|array|min:1',
            'add_title' => 'required_if:status,'.CollectionAdStatusEnum::PUBLISHED.'|array|min:1',
            'add_headline' => 'required_if:status,'.CollectionAdStatusEnum::PUBLISHED.'|array|min:1',
            'add_text' => 'required_if:status,'.CollectionAdStatusEnum::PUBLISHED.'|array|min:1',
            'add_call_to_action' => 'required_if:status,'.CollectionAdStatusEnum::PUBLISHED.'|array|min:1',
            'add_url' => 'required_if:status,'.CollectionAdStatusEnum::PUBLISHED.'|array|min:1',
            'status' => ['required', Rule::in(CollectionAdStatusEnum::members())],
        ];
    }
}
