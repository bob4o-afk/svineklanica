<?php

declare(strict_types=1);

namespace Modules\Identity\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Data\AdminUserData;
use Modules\Identity\Data\LoginData;

/** Admin SPA auth at /api/admin/* (API_SEAM.md). Sanctum session cookie + CSRF. */
final class AdminAuthController
{
    public function login(LoginData $data, Request $request): JsonResponse
    {
        if (! Auth::guard('web')->attempt(['email' => $data->email, 'password' => $data->password])) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $request->session()->regenerate();

        return AdminUserData::fromModel(Auth::guard('web')->user())
            ->toResponse($request)
            ->setStatusCode(200);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(null, 204);
    }

    public function me(Request $request): AdminUserData|JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(null, 401);
        }

        return AdminUserData::fromModel($user);
    }
}
