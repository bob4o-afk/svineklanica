<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Force the ARRAY cache store in tests, isolated per test. phpunit.xml
        // asks for this via CACHE_STORE=array, but the docker env_file puts
        // CACHE_STORE=redis into $_SERVER, which Laravel's env repository reads
        // BEFORE $_ENV — so PHPUnit's value loses and tests would share Redis.
        // On shared Redis the security perimeter (BlacklistMiddleware / honeypot)
        // leaks bans across tests: the tarpit counter accumulates over the whole
        // suite and late tests get spurious 403s. Overriding config here bypasses
        // env precedence entirely. The app is rebuilt per test, so the store
        // starts empty; the flush is belt-and-suspenders.
        config(['cache.default' => 'array']);
        Cache::flush();
    }
}
