<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
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
            'name'  => 'required|string|max:100',
            "phone" => 'nullable|phone:BD',
            'image' => 'nullable|mimes:jpeg,png,jpg,webp|max:5120'
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
            'name'  => __("customValidations.common.name"),
            'phone' => __("customValidations.common.phone"),
            'image' => __("customValidations.common.image"),
        ];
    }
}
