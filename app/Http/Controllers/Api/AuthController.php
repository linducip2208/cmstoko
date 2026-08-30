<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Issue a Sanctum personal access token with valid credentials.
     */
    public function issue(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:120'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial tidak cocok dengan catatan kami.'],
            ]);
        }

        $token = $user->createToken($validated['device_name'] ?? 'api');

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'user' => new ProfileResource($user),
        ], 201);
    }

    public function me(Request $request): ProfileResource
    {
        return new ProfileResource($request->user());
    }

    /**
     * Revoke the current token and issue a fresh one.
     */
    public function refresh(Request $request): JsonResponse
    {
        $current = $request->user()->currentAccessToken();
        $device = $current->name ?? 'api';

        $current?->delete();

        $token = $request->user()->createToken($device);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Token dicabut.']);
    }
}
