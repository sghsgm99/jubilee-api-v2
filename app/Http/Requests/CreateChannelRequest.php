<?php

namespace App\Http\Requests;

use App\Models\Enums\ChannelFacebookTypeEnum;
use App\Models\Enums\ChannelStatusEnum;
use App\Models\Enums\ChannelPlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateChannelRequest extends FormRequest
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
            'platform' => ['required', Rule::in(ChannelPlatformEnum::members())],
            'status' => ['required', Rule::in(ChannelStatusEnum::members())],
            'images' => ['array'],
            'images.*' => ['image', 'max:5000', 'mimes:jpeg,png,jpg,svg,bmp,gif'],
            'fb_page_id' => 'required_if:platform,'.ChannelPlatformEnum::FACEBOOK,
        ];

        if(!$this->has('type') || ChannelFacebookTypeEnum::memberByValue($this->type)->is(ChannelFacebookTypeEnum::CHILD_BM())) {
            $rule += [
                'fb_vertical' => 'required_if:platform,'.ChannelPlatformEnum::FACEBOOK,
                'fb_timezone' => 'required_if:platform,'.ChannelPlatformEnum::FACEBOOK.'|numeric',
                'user_access_token' => 'required_if:platform,'.ChannelPlatformEnum::FACEBOOK,
                
            ];
        } 
        
        if($this->has('type') && ChannelFacebookTypeEnum::memberByValue($this->type)->is(ChannelFacebookTypeEnum::STANDALONE())){
            $rule += [
            'fb_ad_account' => 'required_if:platform,'.ChannelPlatformEnum::FACEBOOK.'|numeric',
                'facebook_access_token' => 'required_if:platform,'.ChannelPlatformEnum::FACEBOOK,
            ];
        }
        
        return $rule;
    }
}
