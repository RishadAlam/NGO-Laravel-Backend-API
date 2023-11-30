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

    /**
     * Validation attributes
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'company_name'          => __("customValidations.common.company_name"),
            'company_short_name'    => __("customValidations.common.company_short_name"),
            'company_address'       => __("customValidations.common.company_address"),
            'company_logo'          => __("customValidations.common.company_logo"),
            'company_old_logo'      => __("customValidations.common.company_old_logo"),
            'company_logo_uri'      => __("customValidations.common.company_logo_uri"),
        ];
    }
}