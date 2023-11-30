<?php

namespace App\Http\Requests\accounts;

use Illuminate\Foundation\Http\FormRequest;

class AccountWithdrawalUpdateRequest extends FormRequest
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
            'amount'                => 'required|numeric',
            'previous_balance'      => 'required|numeric',
            'balance'               => 'required|numeric',
            'description'           => 'nullable',
            'date'                  => 'nullable',
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
            'amount'            => __("customValidations.common.amount"),
            'previous_balance'  => __("customValidations.common.previous_balance"),
            'balance'           => __("customValidations.common.balance"),
            'description'       => __("customValidations.common.description"),
            'date'              => __("customValidations.common.date"),
        ];
    }
}
