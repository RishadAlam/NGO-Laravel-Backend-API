<?php

namespace App\Http\Requests\ClientAccountChecks;

use Illuminate\Foundation\Http\FormRequest;

class LoanAccountCheckStoreRequest extends FormRequest
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
            "account_id"        => "required|numeric",
            "description"       => "sometimes|nullable",
            "next_check_in_at"  => "required|date"
        ];
    }
}
