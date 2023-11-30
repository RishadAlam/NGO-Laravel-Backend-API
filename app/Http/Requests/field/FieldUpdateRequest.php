<?php

namespace App\Http\Requests\field;

use Illuminate\Foundation\Http\FormRequest;

class FieldUpdateRequest extends FormRequest
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
            "name"          => "required|max:100|unique:fields,name,{$this->field}",
            "description"   => "nullable",
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
            'name'          => __("customValidations.common.name"),
            'description'   => __("customValidations.common.description"),
        ];
    }
}
