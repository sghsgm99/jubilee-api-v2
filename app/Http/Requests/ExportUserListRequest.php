<?php

namespace App\Http\Requests;

use App\Models\Enums\ExportFileTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportUserListRequest extends FormRequest
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
            "ids" => "required|array",
            "ids.*" => "integer",
            "filetype" => ['required', Rule::in(ExportFileTypeEnum::members())]
        ];
    }
}
