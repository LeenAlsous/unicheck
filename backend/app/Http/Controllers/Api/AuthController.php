<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Login, logout and the current user's profile.
 */
class AuthController extends Controller
{
    /**
     * Log in with a university ID and password.
     *
     * Returns a bearer token to send as `Authorization: Bearer <token>` on later requests.
     *
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('university_id', trim($request->string('university_id')))->first();

        // Same message for unknown user and wrong password, so IDs can't be guessed.
        if (! $user || ! $user->is_active || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'university_id' => 'Invalid university ID or password.',
            ]);
        }

        $token = $user->createToken($request->input('device_name') ?? 'api');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * The logged-in user.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Log out by revoking the token used for this request.
     */
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
