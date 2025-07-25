<?php

namespace App\Http\Requests\category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryUpdateRequest extends FormRequest
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
            "name"          => "required|max:100|unique:categories,name,{$this->category}",
            "group"         => "required|max:50",
            "description"   => "nullable",
            "saving"        => "required_without:loan|required_if:loan,false|boolean",
            "loan"          => "required_without:saving|required_if:saving,false|boolean",
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
            'group'         => __("customValidations.common.group"),
            'description'   => __("customValidations.common.description"),
            'saving'        => __("customValidations.common.saving"),
            'loan'          => __("customValidations.common.loan"),
        ];
    }
}
