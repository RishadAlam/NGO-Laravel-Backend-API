<?php

namespace App\Http\Requests\client;

use App\Helpers\Helper;
use App\Models\AppConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoanAccountStoreRequest extends FormRequest
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
        $validations = [
            'field_id'                              => 'required|numeric',
            'center_id'                             => 'required|numeric',
            'category_id'                           => 'required|numeric',
            'client_registration_id'                => 'required|numeric',
            'acc_no'                                => 'required',
            'start_date'                            => 'required|date',
            'duration_date'                         => 'required|date',
            'loan_given'                            => 'required|numeric',
            'payable_installment'                   => 'required|numeric|digits_between:1,3',
            'payable_deposit'                       => 'required|numeric',
            'payable_interest'                      => 'required|numeric|digits_between:1,3',
            'total_payable_interest'                => 'required|numeric',
            'total_payable_loan_with_interest'      => 'required|numeric',
            'loan_installment'                      => 'required|numeric',
            'interest_installment'                  => 'required|numeric',
            'creator_id'                            => 'sometimes|nullable|numeric',
            'guarantors.*.name'                     => 'required',
            'guarantors.*.husband_name'             => 'sometimes',
            'guarantors.*.father_name'              => 'required',
            'guarantors.*.mother_name'              => 'required',
            'guarantors.*.nid'                      => 'required|numeric',
            'guarantors.*.dob'                      => 'required|date',
            'guarantors.*.occupation'               => 'required',
            'guarantors.*.relation'                 => 'required',
            'guarantors.*.gender'                   => 'required',
            'guarantors.*.primary_phone'            => 'required|phone:BD',
            'guarantors.*.secondary_phone'          => 'sometimes|nullable|phone:BD',
            'guarantors.*.image'                    => 'required_if:image_uri,|sometimes|nullable|mimes:jpeg,png,jpg,webp|max:5120',
            'guarantors.*.image_uri'                => 'required_if:image,',
            'guarantors.*.signature'                => 'sometimes',
            'guarantors.*.address.street_address'   => 'required',
            'guarantors.*.address.city'             => 'required',
            'guarantors.*.address.word_no'          => 'sometimes|nullable',
            'guarantors.*.address.post_office'      => 'required',
            'guarantors.*.address.police_station'   => 'required',
            'guarantors.*.address.district'         => 'required',
            'guarantors.*.address.division'         => 'required',
        ];

        if (AppConfig::get_config('guarantor_reg_sign_is_required')) {
            $validations['guarantors.*.signature'] = "required";
        }
        return $validations;
    }

    /**
     * Validation attributes
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'field_id'                            => __("customValidations.common.field_id"),
            'center_id'                           => __("customValidations.common.center_id"),
            'category_id'                         => __("customValidations.common.category_id"),
            'client_registration_id'              => __("customValidations.common.client_registration_id"),
            'acc_no'                              => __("customValidations.common.acc_no"),
            'start_date'                          => __("customValidations.common.start_date"),
            'duration_date'                       => __("customValidations.common.duration_date"),
            'payable_installment'                 => __("customValidations.common.payable_installment"),
            'loan_given'                          => __("customValidations.common.loan_given"),
            'payable_deposit'                     => __("customValidations.common.payable_deposit"),
            'payable_interest'                    => __("customValidations.common.payable_interest"),
            'total_payable_interest'              => __("customValidations.common.total_payable_interest"),
            'total_payable_loan_with_interest'    => __("customValidations.common.total_payable_loan_with_interest"),
            'loan_installment'                    => __("customValidations.common.loan_installment"),
            'interest_installment'                => __("customValidations.common.interest_installment"),
            'creator_id'                          => __("customValidations.common.creator_id"),
            'guarantors.*.name'                   => __("customValidations.common.name"),
            'guarantors.*.husband_name'           => __("customValidations.common.husband_name"),
            'guarantors.*.father_name'            => __("customValidations.common.father_name"),
            'guarantors.*.mother_name'            => __("customValidations.common.mother_name"),
            'guarantors.*.nid'                    => __("customValidations.common.nid"),
            'guarantors.*.dob'                    => __("customValidations.common.dob"),
            'guarantors.*.occupation'             => __("customValidations.common.occupation"),
            'guarantors.*.relation'               => __("customValidations.common.relation"),
            'guarantors.*.gender'                 => __("customValidations.common.gender"),
            'guarantors.*.primary_phone'          => __("customValidations.common.primary_phone"),
            'guarantors.*.secondary_phone'        => __("customValidations.common.secondary_phone"),
            'guarantors.*.image'                  => __("customValidations.common.image"),
            'guarantors.*.signature'              => __("customValidations.common.signature"),
            'guarantors.*.address.street_address' => __("customValidations.common.street_address"),
            'guarantors.*.address.city'           => __("customValidations.common.city"),
            'guarantors.*.address.word_no'        => __("customValidations.common.word_no"),
            'guarantors.*.address.post_office'    => __("customValidations.common.post_office"),
            'guarantors.*.address.police_station' => __("customValidations.common.police_station"),
            'guarantors.*.address.district'       => __("customValidations.common.district"),
            'guarantors.*.address.division'       => __("customValidations.common.division"),
        ];
    }
}
