<?php

namespace App\Http\Requests\appConfig;

use Illuminate\Foundation\Http\FormRequest;

class AppSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            "company_name"          => "required",
            "company_short_name"    => "required",
            "company_address"       => "required",
            "company_logo"          => "nullable",
            "company_old_logo"      => "nullable",
            "company_logo_uri"      => "nullable",
        ];
    }
}
