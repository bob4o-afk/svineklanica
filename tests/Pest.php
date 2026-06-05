<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Root unit tests: boot the app (for __() etc.), no DB.
pest()->extend(TestCase::class)->in('Unit');

// Feature tests (root + every module) hit a REAL Postgres (backend.md §13).
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', __DIR__.'/../modules');
