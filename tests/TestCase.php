<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Force the ARRAY cache store in tests, isolated per test — set BEFORE the
        // app boots. phpunit.xml asks for this via CACHE_STORE=array, but the
        // docker env_file puts CACHE_STORE=redis into $_SERVER, which Laravel's env
        // repository reads BEFORE $_ENV — so PHPUnit's value loses and tests would
        // share Redis. On shared Redis the security perimeter (BlacklistMiddleware
        // / honeypot / named rate limiters) leaks state across tests: the tarpit
        // and login counters accumulate over the whole suite and late tests get
        // spurious 403/429s. We must win BEFORE boot because the named rate
        // limiters resolve the RateLimiter (binding its cache store) during
        // provider boot — a post-boot config() override would come too late and
        // leave the limiter pinned to Redis. Overwriting $_SERVER/$_ENV/putenv
        // makes env('CACHE_STORE') return 'array' at bootstrap.
        $_SERVER['CACHE_STORE'] = 'array';
        $_ENV['CACHE_STORE'] = 'array';
        putenv('CACHE_STORE=array');

        parent::setUp();

        // Belt-and-suspenders: the app is rebuilt per test, so the store starts
        // empty; pin the resolved config and flush regardless.
        config(['cache.default' => 'array']);
        Cache::flush();
    }
}
