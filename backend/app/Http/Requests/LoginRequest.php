<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Login form: university ID + password.
 */
class LoginRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Student or staff number. @example I1001 */
            'university_id' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
            /** Shown in the user's list of logged-in devices. @example Pixel 8 */
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}
