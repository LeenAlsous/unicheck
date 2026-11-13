<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Log in with a university ID and password.
     *
     * Returns a token. Send it on every other request as `Authorization: Bearer <token>`.
     *
     * @unauthenticated
     */
    public function login(Request $request)
    {
        $request->validate([
            'university_id' => 'required|string|max:30',
            'password' => 'required|string',
            'device_uid' => 'nullable|string|max:100', // the phone's ID, sent by the app
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('university_id', trim($request->university_id))->first();

        // Same message for an unknown ID and a wrong password, so nobody can guess which IDs exist.
        if (! $user || ! $user->is_active || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'university_id' => 'Invalid university ID or password.',
            ]);
        }

        $tokenName = $request->device_name ?? 'api';

        if ($user->role === 'student') {
            $device = $this->registerPhone($user, $request->device_uid, $request->device_name);
            $tokenName = $device->tokenName();
        }

        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    // Students log in from their phone. A student can use several phones,
    // but a phone that belongs to one student can't be used for another student's account.
    private function registerPhone(User $student, ?string $deviceUid, ?string $name): Device
    {
        if (! $deviceUid) {
            throw ValidationException::withMessages([
                'university_id' => 'Students must log in from the UniCheck phone app.',
            ]);
        }

        $device = Device::where('device_uid', $deviceUid)->first();

        if ($device && $device->student_id != $student->id) {
            throw ValidationException::withMessages([
                'university_id' => 'This phone is registered to another student. Each phone can only be used by one student.',
            ]);
        }

        if (! $device) {
            $device = $student->devices()->create(['device_uid' => $deviceUid, 'name' => $name]);
        }

        $device->update(['last_used_at' => now()]);

        return $device;
    }

    /**
     * The logged-in user.
     */
    public function me(Request $request)
    {
        return $request->user();
    }

    /**
     * Log out (the token stops working).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
