<?php

namespace App\Http\Requests\collection;

use Illuminate\Foundation\Http\FormRequest;

class SavingCollectionUpdateRequest extends FormRequest
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
            'installment'               => 'required|numeric',
            'deposit'                   => 'required|numeric',
            'description'               => 'sometimes|nullable'
        ];
    }
}