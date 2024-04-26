<?php

namespace App\Http\Requests\client;

use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class AccNoUpdateRequest extends FormRequest
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
            'acc_no' => [
                'required',
                Rule::unique('client_registrations', 'acc_no')->ignore($this->clientRegistration),
            ],
        ];
    }
}
