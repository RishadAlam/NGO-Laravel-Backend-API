<?php

namespace App\Http\Requests\client;

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
    protected function failedValidation(Validator $validator)
    {
        $errorMessages = $validator->errors()->toArray();
        // $errorCount = count($errorMessages);
        $formattedErrors = [];

        foreach ($errorMessages as $field => $errors) {
            $fieldNames = explode('.', $field);
            $formattedErrors[array_shift($fieldNames)][] = $this->createNestedArray(array_slice($fieldNames, 1), $errors);
        }

        throw new HttpResponseException(
            response()->json(['errors' => $formattedErrors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY)
        );
    }

    /**
     * Create Array of index from the validation error key.
     *
     * @param array $fields
     * @param array $data
     * @return array
     */
    protected function createNestedArray($fields, $data)
    {
        if (empty($fields)) {
            return $data;
        }

        $currentLevelName = array_shift($fields);
        $subarray = array();
        $subarray[!is_numeric($currentLevelName) ? $currentLevelName : (int) $currentLevelName] = $this->createNestedArray($fields, $data);
        return $subarray;
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
            'nominees.*.nid'                    => 'required',
            'nominees.*.dob'                    => 'required',
            'nominees.*.occupation'             => 'required',
            'nominees.*.relation'               => 'required',
            'nominees.*.gender'                 => 'required',
            'nominees.*.primary_phone'          => 'required',
            'nominees.*.secondary_phone'        => 'sometimes',
            'nominees.*.image'                  => 'required',
            'nominees.*.signature'              => 'sometimes',
            'nominees.*.address.street_address' => 'required',
            'nominees.*.address.city'           => 'required',
            'nominees.*.address.word_no'        => 'sometimes',
            'nominees.*.address.post_office'    => 'required',
            'nominees.*.address.police_station' => 'required',
            'nominees.*.address.district'       => 'required',
            'nominees.*.address.division'       => 'required',
        ];
    }
}
