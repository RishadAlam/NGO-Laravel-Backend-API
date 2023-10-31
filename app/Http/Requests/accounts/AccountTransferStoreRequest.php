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
}
