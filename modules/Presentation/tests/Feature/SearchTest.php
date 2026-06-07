<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Procurement\Jobs\EmbedRecordsJob;
use Modules\Procurement\Models\Tender;

/** A 384-dim one-hot vector (matches config('vector.dimensions')) — lets a test pin
 * which stored row a faked query embedding is "closest" to under cosine distance. */
function oneHot(int $i): array
{
    $v = array_fill(0, (int) config('vector.dimensions', 384), 0.0);
    $v[$i] = 1.0;

    return $v;
}

/** Fake Google's embedding endpoint to return $vector for every requested text. */
function fakeGoogleEmbedding(array $vector): void
{
    Http::fake([
        '*batchEmbedContents*' => function ($request) use ($vector) {
            $count = max(1, count($request->data()['requests'] ?? [[]]));

            return Http::response(['embeddings' => array_fill(0, $count, ['values' => $vector])]);
        },
        '*' => Http::response([], 200),
    ]);
}

it('falls back to keyword search when Google is not configured', function () {
    config(['services.google.key' => null]);

    Tender::factory()->create(['title' => 'Доставка на лаптопи за болница']);

    $response = $this->getJson('/api/search?q=лаптопи');

    $response->assertSuccessful();
    expect($response->json('tenders'))->toHaveCount(1)
        ->and($response->json('tenders.0.title'))->toContain('лаптопи');
});

it('returns the semantically closest tender first via pgvector', function () {
    config(['services.google.key' => 'test-key', 'services.google.refine_search' => false]);

    // Far vector for B, exact-match vector for A; the faked query embedding is A's.
    $near = Tender::factory()->create(['title' => 'Ремонт на пътна настилка', 'description_embedding' => oneHot(0)]);
    $far = Tender::factory()->create(['title' => 'Доставка на канцеларски материали', 'description_embedding' => oneHot(7)]);

    fakeGoogleEmbedding(oneHot(0));

    $response = $this->getJson('/api/search?q=ремонт+на+път');

    $response->assertSuccessful();
    $ids = array_column($response->json('tenders'), 'public_id');
    expect($ids[0])->toBe($near->public_id)
        ->and($ids)->toContain($far->public_id);
});

it('excludes tenders without an embedding from vector results', function () {
    config(['services.google.key' => 'test-key', 'services.google.refine_search' => false]);

    Tender::factory()->create(['title' => 'Без вектор']); // description_embedding stays null
    fakeGoogleEmbedding(oneHot(0));

    // No embedded rows at all -> the service degrades to keyword search, which still
    // matches by title, so the box is never empty just because embeddings are missing.
    $response = $this->getJson('/api/search?q=без');

    $response->assertSuccessful();
    expect($response->json('tenders'))->toHaveCount(1);
});

it('dispatches the async embedding job with --queue instead of running inline', function () {
    Queue::fake();

    $this->artisan('search:embed', ['--queue' => true])->assertSuccessful();

    Queue::assertPushed(EmbedRecordsJob::class);
});

it('embeds rows and stores the vector via search:embed', function () {
    config(['services.google.key' => 'test-key']);
    fakeGoogleEmbedding(oneHot(3));

    $tender = Tender::factory()->create(['title' => 'Доставка на медицинско оборудване']);
    expect($tender->fresh()->description_embedding)->toBeNull();

    $this->artisan('search:embed', ['--type' => 'tenders'])->assertSuccessful();

    $embedding = $tender->fresh()->description_embedding;
    expect($embedding)->toBeArray()
        ->and($embedding)->toHaveCount((int) config('vector.dimensions', 384));
});
