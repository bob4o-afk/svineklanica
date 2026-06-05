<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('runs against an isolated test database with pgvector', function () {
    // Proves test isolation: never the dev/prod DB.
    expect(DB::connection()->getDatabaseName())->toBe('liberhack_test');

    // RefreshDatabase ran our migrations on it — incl. the pgvector extension.
    $extension = DB::selectOne("SELECT extname FROM pg_extension WHERE extname = 'vector'");
    expect($extension?->extname)->toBe('vector');
});
