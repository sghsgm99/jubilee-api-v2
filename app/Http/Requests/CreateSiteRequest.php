<?php

namespace App\Http\Requests;

use App\Models\Enums\SiteStatusEnum;
use App\Models\Enums\SitePlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateSiteRequest extends FormRequest
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
            'user_id' => 'required|integer',
            'name' => 'required|string',
            'url' => 'required|string',
            'client_key' => 'nullable|required_if:platform,' . SitePlatformEnum::WORDPRESS,
            'client_secret_key' => 'nullable|required_if:platform,' . SitePlatformEnum::WORDPRESS,
            'description' => 'required',
            'platform' => ['required', Rule::in(SitePlatformEnum::members())],
            'status' => ['required', Rule::in(SiteStatusEnum::members())]
        ];
    }

    public function messages()
    {
        return [
            'client_key.required_if' => 'The client key field is required when platform is ' . SitePlatformEnum::WORDPRESS()->getLabel(),
            'client_secret_key.required_if' => 'The client secret key field is required when platform is ' . SitePlatformEnum::WORDPRESS()->getLabel(),
        ];
    }
}
