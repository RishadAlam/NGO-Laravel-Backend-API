<?php

namespace App\Http\Requests\client;

use App\Helpers\Helper;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SavingAccountStoreRequest extends FormRequest
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
            'field_id'                          => 'required|numeric',
            'center_id'                         => 'required|numeric',
            'category_id'                       => 'required|numeric',
            'client_registration_id'            => 'required|numeric',
            'acc_no'                            => 'required|numeric',
            'start_date'                        => 'required|date',
            'duration_date'                     => 'required|date',
            'payable_installment'               => 'required|numeric',
            'payable_deposit'                   => 'required|numeric',
            'payable_interest'                  => 'required|numeric',
            'total_deposit_without_interest'    => 'required|numeric',
            'total_deposit_with_interest'       => 'required|numeric',
            'creator_id'                        => 'sometimes|numeric',
            'nominees.*.name'                   => 'required',
            'nominees.*.husband_name'           => 'sometimes',
            'nominees.*.father_name'            => 'required',
            'nominees.*.mother_name'            => 'required',
            'nominees.*.nid'                    => 'required|numeric',
            'nominees.*.dob'                    => 'required|date',
            'nominees.*.occupation'             => 'required',
            'nominees.*.relation'               => 'required',
            'nominees.*.gender'                 => 'required',
            'nominees.*.primary_phone'          => 'required|phone:BD',
            'nominees.*.secondary_phone'        => 'nullable|phone:BD',
            'nominees.*.image'                  => 'required|mimes:jpeg,png,jpg,webp|max:5120',
            'nominees.*.signature'              => 'sometimes',
            'nominees.*.address.street_address' => 'required',
            'nominees.*.address.city'           => 'required',
            'nominees.*.address.word_no'        => 'nullable|numeric',
            'nominees.*.address.post_office'    => 'required',
            'nominees.*.address.police_station' => 'required',
            'nominees.*.address.district'       => 'required',
            'nominees.*.address.division'       => 'required',
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
            'category_id'                       => __("customValidations.common.category_id"),
            'client_registration_id'            => __("customValidations.common.client_registration_id"),
            'acc_no'                            => __("customValidations.common.acc_no"),
            'start_date'                        => __("customValidations.common.start_date"),
            'duration_date'                     => __("customValidations.common.duration_date"),
            'payable_installment'               => __("customValidations.common.payable_installment"),
            'payable_deposit'                   => __("customValidations.common.payable_deposit"),
            'payable_interest'                  => __("customValidations.common.payable_interest"),
            'total_deposit_without_interest'    => __("customValidations.common.total_deposit_without_interest"),
            'total_deposit_with_interest'       => __("customValidations.common.total_deposit_with_interest"),
            'creator_id'                        => __("customValidations.common.creator_id"),
            'nominees.*.name'                   => __("customValidations.common.name"),
            'nominees.*.husband_name'           => __("customValidations.common.husband_name"),
            'nominees.*.father_name'            => __("customValidations.common.father_name"),
            'nominees.*.mother_name'            => __("customValidations.common.mother_name"),
            'nominees.*.nid'                    => __("customValidations.common.nid"),
            'nominees.*.dob'                    => __("customValidations.common.dob"),
            'nominees.*.occupation'             => __("customValidations.common.occupation"),
            'nominees.*.relation'               => __("customValidations.common.relation"),
            'nominees.*.gender'                 => __("customValidations.common.gender"),
            'nominees.*.primary_phone'          => __("customValidations.common.primary_phone"),
            'nominees.*.secondary_phone'        => __("customValidations.common.secondary_phone"),
            'nominees.*.image'                  => __("customValidations.common.image"),
            'nominees.*.signature'              => __("customValidations.common.signature"),
            'nominees.*.address.street_address' => __("customValidations.common.street_address"),
            'nominees.*.address.city'           => __("customValidations.common.city"),
            'nominees.*.address.word_no'        => __("customValidations.common.word_no"),
            'nominees.*.address.post_office'    => __("customValidations.common.post_office"),
            'nominees.*.address.police_station' => __("customValidations.common.police_station"),
            'nominees.*.address.district'       => __("customValidations.common.district"),
            'nominees.*.address.division'       => __("customValidations.common.division"),
        ];
    }
}
