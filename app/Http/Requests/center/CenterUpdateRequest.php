<?php

namespace App\Http\Requests\center;

use Illuminate\Foundation\Http\FormRequest;

class CenterUpdateRequest extends FormRequest
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
            "name"          => "required|max:100|unique:centers,name,{$this->center}",
            "field_id"      => "required|integer",
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
            'field_id'      => __("customValidations.common.field"),
            'description'   => __("customValidations.common.description"),
        ];
    }
}
