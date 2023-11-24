<?php

namespace App\Http\Requests\client;

use App\Models\AppConfig;
use Illuminate\Foundation\Http\FormRequest;

class ClientRegistrationStoreRequest extends FormRequest
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
        $validations = [
            'field_id'          => "required",
            'center_id'         => "required",
            'acc_no'            => "required|unique:client_registrations,acc_no",
            'name'              => "required",
            'father_name'       => "required",
            'husband_name'      => "nullable",
            'mother_name'       => "required",
            'nid'               => "required|unique:client_registrations,nid",
            'dob'               => "required|date",
            'occupation'        => "required",
            'religion'          => "required",
            'gender'            => "required",
            'primary_phone'     => "required|phone:BD",
            'secondary_phone'   => "nullable|phone:BD",
            'image'             => "required|mimes:jpeg,png,jpg,webp|max:5120",
            'signature'         => "nullable",
            'share'             => "required|integer",
            'annual_income'     => "nullable",
            'bank_acc_no'       => "nullable",
            'bank_check_no'     => "nullable",
            'present_address'   => "required|json",
            'permanent_address' => "required|json"
        ];

        if (AppConfig::get_config('client_reg_sign_is_required')) {
            $validations['signature'] = "required";
        }

        return $validations;
    }
}
