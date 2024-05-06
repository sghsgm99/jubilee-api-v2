<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdsetViaCustomAudienceRequest extends FormRequest
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
            'channel_id' => 'string|required',
            'name' => 'string|required',
            'bid_amount' => 'integer|required',
            'daily_budget' => 'integer|required',
            'campaign_id' => 'integer|required',
            'custom_audience_id' => 'array|required',
            'countries' => 'array|required'
        ];
    }
}
