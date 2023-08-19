<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
            'email' => 'required|email|unique:users,email',
            'name' => 'required',
            'password' => 'required|min:4',
            'confirm_password' => 'required|same:password'
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'The :attribute is required.',
            'email.email' => 'The email must be a valid email address.',
            'password' => 'Password must have at least 4 characters.',
            'confirm_password' => 'Passwords must match.'
        ];
    }
}
