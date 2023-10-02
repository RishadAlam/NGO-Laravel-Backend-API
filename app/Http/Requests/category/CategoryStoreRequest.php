<?php

namespace App\Http\Requests\category;

use Illuminate\Foundation\Http\FormRequest;

class CategoryStoreRequest extends FormRequest
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
            "name"          => "required|max:100|unique:categories,name",
            "description"   => "nullable",
            "saving"        => "required_without:loan|required_if:loan,false|boolean",
            "loan"          => "required_without:saving|required_if:saving,false|boolean",
        ];
    }
}
