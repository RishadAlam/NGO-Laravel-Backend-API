<?php

namespace App\Http\Requests\accounts;

use Illuminate\Foundation\Http\FormRequest;

class AccountStoreRequest extends FormRequest
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
            "name"              => "required|max:100",
            "acc_no"            => "nullable|unique:accounts,acc_no",
            "acc_details"       => "nullable",
            "initial_balance"   => "nullable"
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
            'acc_no'            => __("customValidations.common.acc_no"),
            'acc_details'       => __("customValidations.common.acc_details"),
            'initial_balance'   => __("customValidations.common.initial_balance"),
        ];
    }
}
