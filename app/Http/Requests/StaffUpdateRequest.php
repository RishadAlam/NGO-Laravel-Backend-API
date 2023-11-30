<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StaffUpdateRequest extends FormRequest
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
            'name'      => 'required|string|max:100',
            'email'     => "required|email|unique:users,email,{$this->user}",
            'password'  => [
                'nullable',
                'min:8',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain at least one special character
            ],
            'confirm_password'  => 'nullable|same:password',
            "phone"             => 'nullable|phone:BD',
            'role'              => 'required|integer'
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
            'name'              => __("customValidations.common.name"),
            'email'             => __("customValidations.common.email"),
            'phone'             => __("customValidations.common.phone"),
            'role'              => __("customValidations.common.role"),
            'password'          => __("customValidations.common.password"),
            'confirm_password'  => __("customValidations.common.confirm_password"),
        ];
    }
}