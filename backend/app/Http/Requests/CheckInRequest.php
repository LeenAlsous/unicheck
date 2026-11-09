<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A student's QR scan with the phone's location.
 */
class CheckInRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            /** The text inside the scanned QR code. @example UC1.42.179421840.ToPFKhS-UKQI7KY--PVGfT */
            'code' => ['required', 'string', 'max:200'],
            /** @example 31.9539 */
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            /** @example 35.9106 */
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            /** GPS accuracy reported by the phone, in meters. @example 12 */
            'accuracy_meters' => ['required', 'numeric', 'min:0'],
            /** True when the phone reports the location as mocked (Android `isMocked`). */
            'is_mocked' => ['sometimes', 'boolean'],
        ];
    }
}
