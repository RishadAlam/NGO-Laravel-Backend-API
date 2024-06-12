<?php

namespace App\Http\Requests\ClientAccClosing;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavingAccountClosingRequest extends FormRequest
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
            'account_id'            => 'required|numeric',
            'balance'               => 'required|numeric',
            'interest'              => 'required|numeric',
            'total_balance'         => 'required|numeric',
            'closing_fee'           => 'required|numeric',
            'closing_fee_acc_id'    => 'required|numeric',
            'description'           => 'required',
        ];
    }
}
