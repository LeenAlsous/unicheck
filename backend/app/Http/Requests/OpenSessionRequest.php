<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Start a lecture session: optional room and check-in window length.
 */
class OpenSessionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** Room for this lecture. Defaults to the section's usual room. */
            'room_id' => ['nullable', 'integer', 'exists:rooms,id'],
            /** How long students can check in, in minutes. Defaults to 15. @example 15 */
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
        ];
    }
}
