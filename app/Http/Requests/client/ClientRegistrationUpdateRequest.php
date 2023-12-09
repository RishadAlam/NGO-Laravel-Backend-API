<?php

namespace App\Http\Requests\client;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClientRegistrationUpdateRequest extends FormRequest
{
    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator  $validator)
    {
        $errorMessages = $validator->errors()->toArray();
        // $errorCount = count($errorMessages);
        $formattedErrors = [];

        foreach ($errorMessages as $field => $errors) {
            $fieldNames = explode('.', $field);
            Helper::createNestedArray($formattedErrors, $fieldNames, $errors);
        }

        throw new HttpResponseException(
            response()->json(['errors' => $formattedErrors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }

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
            'field_id'                          => "required",
            'center_id'                         => "required",
            'acc_no'                            => ['required', Rule::unique('client_registrations', 'acc_no')->ignore(request('id'))],
            'name'                              => "required",
            'father_name'                       => "required",
            'husband_name'                      => "nullable",
            'mother_name'                       => "required",
            'nid'                               => ['required', Rule::unique('client_registrations', 'nid')->ignore(request('id'))],
            'dob'                               => "required|date",
            'occupation'                        => "required",
            'religion'                          => "required",
            'gender'                            => "required",
            'primary_phone'                     => "required|phone:BD",
            'secondary_phone'                   => "nullable|phone:BD",
            'image'                             => "nullable|mimes:jpeg,png,jpg,webp|max:5120",
            'signature'                         => "nullable",
            'share'                             => "required|integer",
            'annual_income'                     => "nullable",
            'bank_acc_no'                       => "nullable",
            'bank_check_no'                     => "nullable",
            'present_address.street_address'    => 'required',
            'present_address.city'              => 'required',
            'present_address.word_no'           => 'nullable|numeric',
            'present_address.post_office'       => 'required',
            'present_address.police_station'    => 'required',
            'present_address.district'          => 'required',
            'present_address.division'          => 'required',
            'permanent_address.street_address'  => 'required',
            'permanent_address.city'            => 'required',
            'permanent_address.word_no'         => 'nullable|numeric',
            'permanent_address.post_office'     => 'required',
            'permanent_address.police_station'  => 'required',
            'permanent_address.district'        => 'required',
            'permanent_address.division'        => 'required',
        ];
    }

    /**
     * Validation attributes
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'field_id'                          => __("customValidations.common.field_id"),
            'center_id'                         => __("customValidations.common.center_id"),
            'acc_no'                            => __("customValidations.common.acc_no"),
            'name'                              => __("customValidations.common.name"),
            'husband_name'                      => __("customValidations.common.husband_name"),
            'father_name'                       => __("customValidations.common.father_name"),
            'mother_name'                       => __("customValidations.common.mother_name"),
            'nid'                               => __("customValidations.common.nid"),
            'dob'                               => __("customValidations.common.dob"),
            'occupation'                        => __("customValidations.common.occupation"),
            'religion'                          => __("customValidations.common.religion"),
            'gender'                            => __("customValidations.common.gender"),
            'primary_phone'                     => __("customValidations.common.primary_phone"),
            'secondary_phone'                   => __("customValidations.common.secondary_phone"),
            'image'                             => __("customValidations.common.image"),
            'signature'                         => __("customValidations.common.signature"),
            'share'                             => __("customValidations.common.share"),
            'annual_income'                     => __("customValidations.common.annual_income"),
            'bank_acc_no'                       => __("customValidations.common.bank_acc_no"),
            'bank_check_no'                     => __("customValidations.common.bank_check_no"),
            'present_address.street_address'    => __("customValidations.common.street_address"),
            'present_address.city'              => __("customValidations.common.city"),
            'present_address.word_no'           => __("customValidations.common.word_no"),
            'present_address.post_office'       => __("customValidations.common.post_office"),
            'present_address.police_station'    => __("customValidations.common.police_station"),
            'present_address.district'          => __("customValidations.common.district"),
            'present_address.division'          => __("customValidations.common.division"),
            'permanent_address.street_address'  => __("customValidations.common.street_address"),
            'permanent_address.city'            => __("customValidations.common.city"),
            'permanent_address.word_no'         => __("customValidations.common.word_no"),
            'permanent_address.post_office'     => __("customValidations.common.post_office"),
            'permanent_address.police_station'  => __("customValidations.common.police_station"),
            'permanent_address.district'        => __("customValidations.common.district"),
            'permanent_address.division'        => __("customValidations.common.division"),
        ];
    }
}
