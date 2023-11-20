<?php

namespace App\Http\Requests\client;

use Illuminate\Foundation\Http\FormRequest;

class LoanRegistrationStoreRequest extends FormRequest
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
            'field_id'                          => 'required|numeric',
            'center_id'                         => 'required|numeric',
            'category_id'                       => 'required|numeric',
            'client_registration_id'            => 'required|numeric',
            'acc_no'                            => 'required|numeric',
            'start_date'                        => 'required|numeric',
            'duration_date'                     => 'required|numeric',
            'loan_given'                        => 'required|numeric',
            'payable_deposit'                   => 'required|numeric',
            'payable_installment'               => 'required|numeric',
            'payable_interest'                  => 'required|numeric',
            'total_payable_loan_with_interest'  => 'required|numeric',
            'loan_installment'                  => 'required|numeric',
            'interest_installment'              => 'required|numeric',
            'guarantors'                        => 'required|json',
            'creator_id'                        => 'nullable|numeric'
        ];
    }
}
