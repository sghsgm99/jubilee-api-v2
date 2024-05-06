<?php

namespace App\Http\Requests;

use App\Models\Enums\ReportPlatformEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportGoogleReportRequest extends FormRequest
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
            "from" => "required|string",
            "to" => "required|string",
            "platform" => ['nullable', Rule::in(ReportPlatformEnum::members())]
        ];
    }
}
