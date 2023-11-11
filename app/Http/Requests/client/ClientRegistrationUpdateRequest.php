<?php

namespace App\Http\Requests\client;

use Illuminate\Foundation\Http\FormRequest;

class ClientRegistrationUpdateRequest extends FormRequest
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
            'name'              => "required",
            'father_name'       => "required_if:husband_name,''",
            'husband_name'      => "required_if:father_name,''",
            'mother_name'       => "required",
            'nid'               => "required",
            'dob'               => "required|date",
            'occupation'        => "required",
            'religion'          => "required",
            'gender'            => "required",
            'primary_phone'     => "required|phone:BD",
            'secondary_phone'   => "nullable|phone:BD",
            'image'             => "nullable",
            'share'             => "required|integer",
            'annual_income'     => "nullable",
            'bank_acc_no'       => "nullable",
            'bank_check_no'     => "nullable",
            'present_address'   => "required|json",
            'permanent_address' => "required|json"
        ];
    }
}
