<?php

namespace App\Http\Requests\collection;

use Illuminate\Foundation\Http\FormRequest;

class SavingCollectionStoreRequest extends FormRequest
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
            'field_id'                  => 'required|numeric',
            'center_id'                 => 'required|numeric',
            'category_id'               => 'required|numeric',
            'saving_account_id'         => 'required|numeric',
            'client_registration_id'    => 'required|numeric',
            'account_id'                => 'required|numeric',
            'acc_no'                    => 'required|numeric',
            'installment'               => 'required|numeric',
            'deposit'                   => 'required|numeric',
            'description'               => 'sometimes|nullable'
        ];
    }
}