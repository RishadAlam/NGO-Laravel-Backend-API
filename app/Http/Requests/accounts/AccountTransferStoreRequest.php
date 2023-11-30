<?php

namespace App\Http\Requests\accounts;

use Illuminate\Foundation\Http\FormRequest;

class AccountTransferStoreRequest extends FormRequest
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
            'tx_acc_id'         => 'required',
            'rx_acc_id'         => 'required',
            'amount'            => 'required|numeric|min:1',
            'tx_prev_balance'   => 'required|numeric',
            'rx_prev_balance'   => 'required|numeric',
            'description'       => 'nullable',
            'date'              => 'nullable'
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
            'tx_acc_id'         => __("customValidations.common.tx_acc_id"),
            'rx_acc_id'         => __("customValidations.common.rx_acc_id"),
            'amount'            => __("customValidations.common.amount"),
            'tx_prev_balance'   => __("customValidations.common.tx_prev_balance"),
            'rx_prev_balance'   => __("customValidations.common.rx_prev_balance"),
            'description'       => __("customValidations.common.description"),
            'date'              => __("customValidations.common.date"),
        ];
    }
}
