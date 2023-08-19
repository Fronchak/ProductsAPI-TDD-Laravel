<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductStoreRequest extends FormRequest
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
            'name' => 'required|min:3|max:150|unique:products,name',
            'description' => 'required|min:10',
            'price' => 'required|numeric|gt:0.0'
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'The :attribute is required.',
            'min' => 'The :attribute must have at least :min characters.',
            'max' => 'The :attribute cannot have more than :max characters.',
            'name.unique' => 'The name is already been used.',
            'price.numeric' => 'The price must be a valid number.',
            'price.gt' => 'The price must be positive.'
        ];
    }
}
