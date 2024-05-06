<?php

namespace App\Http\Requests;

use App\Models\Enums\ChannelFacebookTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;

class UpdateChannelRequest extends FormRequest
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
        $rule = [
            'title' => 'required',
            'content' => 'required',
            'type' => ['required', Rule::in(ChannelFacebookTypeEnum::members())],
            'status' => ['required', Rule::in(ChannelStatusEnum::members())],
            'images' => ['array'],
            'images.*' => ['image', 'max:5000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
        ];
        if($this->has('type') && ChannelFacebookTypeEnum::memberByValue($this->type)->is(ChannelFacebookTypeEnum::STANDALONE())) {
            $rule += [
                'fb_ad_account' => 'required|numeric',
                'facebook_access_token' => 'required',
                'fb_page_id' => 'required',
            ];   
        }

        return $rule;

    }
}
