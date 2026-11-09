<?php

namespace App\Http\Requests;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * An instructor setting or correcting one student's attendance.
 */
class MarkAttendanceRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(AttendanceStatus::class)],
            /** Reason for the change, e.g. a medical excuse. */
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
