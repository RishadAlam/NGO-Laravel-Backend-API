<?php

namespace App\Http\Requests\accounts;

use Illuminate\Foundation\Http\FormRequest;

class ExpenseUpdateRequest extends FormRequest
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
            'expense_category_id'   => 'required',
            'amount'                => 'required|numeric',
            'description'           => 'nullable',
            'date'                  => 'nullable'
        ];
    }
}
