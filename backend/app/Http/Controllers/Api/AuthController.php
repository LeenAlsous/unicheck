<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('university_id', trim($request->university_id))->first();

        // Same message for an unknown ID and a wrong password, so nobody can guess which IDs exist.
        if (! $user || ! $user->is_active || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'university_id' => 'Invalid university ID or password.',
            ]);
        }

        $token = $user->createToken($request->device_name ?? 'api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
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
