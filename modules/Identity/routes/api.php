<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Admin\SecurityController;
use Modules\Identity\Http\Controllers\AuthController;
use Modules\Identity\Http\Controllers\GateController;

// Loaded by IdentityServiceProvider under the 'api' middleware + '/api' prefix.

// There is ONE login surface: the gated /api/admin/login below (the web client uses it).
// The bare /api/login is intentionally NOT a route here — it's a honeypot decoy
// (config/honeypot.php), so anyone hitting it is trapped + blacklisted.

// Whole-site blacklist gate (security.md §3). Caddy `forward_auth` calls this
// before serving the SPA shell to a page navigation: the BlacklistMiddleware on
// the `api` group runs FIRST and 403s a banned caller, so a ban blocks the ENTIRE
// page — not just /api. A clean caller gets 204 and Caddy proceeds to serve the
// app. Public + rate-limited; the tarpit/throttle keep it from being hammered.
Route::middleware('throttle:public')->get('/_gate', GateController::class)->name('security.gate');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('/user', [AuthController::class, 'me'])->name('auth.me');
});

// The ADMIN auth surface the web client uses (login → session → me/logout). It lives under the
// `/admin` prefix so AdminWhitelistMiddleware (on the `api` group, security.md §4) gates it: only
// allow-listed IPs may reach it — anyone else is auto-blacklisted + 404'd. Same controller as the
// routes above; those stay for native clients + the perimeter tests. NB: a session minted via the
// ungated `/login` still can't drive the console — the IP gate sits in front of every `/admin/*`.
Route::prefix('admin')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('admin.auth.login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout'])->name('admin.auth.logout');
        Route::get('/me', [AuthController::class, 'me'])->name('admin.auth.me');
    });
});

// Admin security console — authenticated + admin only. The IP allow-list is
// read-only (env is the single source of truth, security.md §4).
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function (): void {
    Route::get('/security/whitelist', [SecurityController::class, 'whitelist'])
        ->name('admin.security.whitelist');
});
