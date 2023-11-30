<?php

namespace App\Http\Requests\appConfig;

use Illuminate\Foundation\Http\FormRequest;

class ApprovalsRequest extends FormRequest
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
            "approvals" => "required"
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
            'approvals'  => __("customValidations.common.approvals"),
        ];
    }
}
