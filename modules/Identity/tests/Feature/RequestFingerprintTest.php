<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Modules\Identity\Security\Fingerprint\RequestFingerprint;

// Unit coverage for the multi-signal extractor (security.md §3, §10).

it('extracts every identity signal present on a request', function () {
    $request = Request::create('/api/x', 'GET', server: [
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'HTTP_ACCEPT_LANGUAGE' => 'bg-BG',
        'HTTP_'.str_replace('-', '_', strtoupper(RequestFingerprint::CLIENT_ID_HEADER)) => 'ls-id',
        'HTTP_'.str_replace('-', '_', strtoupper(RequestFingerprint::CLIENT_FP_HEADER)) => 'canvas-fp',
    ], cookies: [RequestFingerprint::COOKIE => 'dev-cookie']);

    $signals = RequestFingerprint::signals($request);

    expect($signals)->toHaveKeys(['ip', 'device', 'client', 'clientfp', 'headerfp'])
        ->and($signals['device'])->toBe('dev-cookie')
        ->and($signals['client'])->toBe('ls-id')
        ->and($signals['clientfp'])->toBe('canvas-fp');
});

it('produces a stable header fingerprint for the same headers', function () {
    $make = fn () => Request::create('/x', 'GET', server: [
        'HTTP_USER_AGENT' => 'curl/8.0',
        'HTTP_ACCEPT_LANGUAGE' => 'en',
    ]);

    expect(RequestFingerprint::headerFingerprint($make()))
        ->toBe(RequestFingerprint::headerFingerprint($make()))
        ->and(RequestFingerprint::headerFingerprint($make()))->not->toBeEmpty();
});

it('drops empty signals so a bare request has no false identity', function () {
    // No UA, no language, no cookie → header fingerprint is empty, not a constant
    // hash that would collide every anonymous client into one banned identity.
    // (Symfony's Request::create injects a default UA/Accept, so blank them.)
    $request = Request::create('/x', 'GET', server: [
        'HTTP_USER_AGENT' => '',
        'HTTP_ACCEPT' => '',
        'HTTP_ACCEPT_LANGUAGE' => '',
        'HTTP_ACCEPT_ENCODING' => '',
    ]);

    $signals = RequestFingerprint::signals($request);

    expect($signals)->not->toHaveKey('device')
        ->and($signals)->not->toHaveKey('headerfp');
});
