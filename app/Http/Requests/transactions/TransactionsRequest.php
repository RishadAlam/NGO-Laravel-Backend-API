<?php

namespace App\Http\Requests\transactions;

use Illuminate\Foundation\Http\FormRequest;

class TransactionsRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tx_acc_id'         => 'required|integer',
            'rx_acc_id'         => 'required|integer',
            'amount'            => 'required|integer|min:1',
            'type'              => 'required|string|in:saving_to_saving,saving_to_loan,loan_to_saving,loan_to_loan',
            'description'       => 'nullable|string',
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
            'tx_acc_id'  => __("customValidations.common.sender_account"),
            'rx_acc_id'  => __("customValidations.common.receiver_account"),
            'amount'     => __("customValidations.common.amount"),
            'type'       => __("customValidations.common.transaction_type"),
            'description' => __("customValidations.common.description"),
        ];
    }
}
