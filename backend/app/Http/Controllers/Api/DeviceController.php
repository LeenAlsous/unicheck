<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * My phones (students).
     *
     * Every phone the student has logged in from. `is_this_phone` marks the one making the request.
     */
    public function index(Request $request)
    {
        $currentToken = $request->user()->currentAccessToken()?->name;

        $devices = $request->user()->devices()->orderByDesc('last_used_at')->get();

        foreach ($devices as $device) {
            $device->is_this_phone = $device->tokenName() === $currentToken;
        }

        return $devices;
    }

    /**
     * Remove one of my phones.
     *
     * For a lost or sold phone: it is logged out, and another student can use it after that.
     */
    public function destroy(Request $request, Device $device)
    {
        if ($device->student_id != $request->user()->id) {
            abort(404);
        }

        // Log the phone out.
        $request->user()->tokens()->where('name', $device->tokenName())->delete();
        $device->delete();

        return response()->noContent();
    }
}
