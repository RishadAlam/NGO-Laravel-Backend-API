<?php

namespace App\Http\Requests\accounts;

use Illuminate\Foundation\Http\FormRequest;

class IncomeStoreRequest extends FormRequest
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
            'account_id'            => 'required',
            'income_category_id'    => 'required',
            'amount'                => 'required|numeric|min:1',
            'previous_balance'      => 'required|numeric',
            'balance'               => 'required|numeric',
            'description'           => 'nullable',
            'date'                  => 'nullable'
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
            'account_id'            => __("customValidations.common.account"),
            'income_category_id'    => __("customValidations.common.income_category"),
            'amount'                => __("customValidations.common.amount"),
            'previous_balance'      => __("customValidations.common.previous_balance"),
            'balance'               => __("customValidations.common.balance"),
            'description'           => __("customValidations.common.description"),
            'date'                  => __("customValidations.common.date"),
        ];
    }
}
