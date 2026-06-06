<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Data\LoginData;
use Modules\Identity\Data\UserData;

/**
 * Thin auth controller for the SPA (backend.md §2). Uses Sanctum stateful
 * (session-cookie) auth — no bearer token is handed to JavaScript.
 */
final class AuthController
{
    /** Authenticate and start a session (httpOnly cookie). */
    public function login(LoginData $data, Request $request): JsonResponse
    {
        if (! Auth::guard('web')->attempt(['email' => $data->email, 'password' => $data->password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Prevent session fixation.
        $request->session()->regenerate();

        // Spatie Data defaults a POST response to 201 Created; logging in starts
        // a session, it does NOT create a REST resource, so force 200 OK (matches
        // /api/user and logout). Going through toResponse() keeps Spatie's exact
        // serialization (camelCase keys, wrapping), only the status is overridden.
        return UserData::fromModel(Auth::guard('web')->user())
            ->toResponse($request)
            ->setStatusCode(200);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => __('auth.logged_out')]);
    }

    /** The currently authenticated user. */
    public function me(Request $request): UserData
    {
        return UserData::fromModel($request->user());
    }
}
