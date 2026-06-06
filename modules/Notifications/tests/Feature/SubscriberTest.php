<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Modules\Notifications\Jobs\NotifySubscribersJob;
use Modules\Notifications\Models\Subscriber;

it('lets anyone subscribe and normalizes the email', function () {
    $this->postJson('/api/subscribe', ['email' => '  Boris@Example.ORG '])
        ->assertCreated()
        ->assertJsonPath('status', 'subscribed');

    expect(Subscriber::where('email', 'boris@example.org')->exists())->toBeTrue();
});

it('is idempotent and re-subscribe clears the unsubscribed flag', function () {
    $this->postJson('/api/subscribe', ['email' => 'a@b.org'])->assertCreated();
    $sub = Subscriber::firstWhere('email', 'a@b.org');
    $sub->update(['unsubscribed_at' => now()]);

    $this->postJson('/api/subscribe', ['email' => 'a@b.org'])->assertCreated();

    expect(Subscriber::where('email', 'a@b.org')->count())->toBe(1);
    expect($sub->fresh()->unsubscribed_at)->toBeNull();
});

it('rejects an invalid email', function () {
    $this->postJson('/api/subscribe', ['email' => 'not-an-email'])->assertStatus(422);
});

it('unsubscribes via token', function () {
    $this->postJson('/api/subscribe', ['email' => 'c@d.org'])->assertCreated();
    $token = Subscriber::firstWhere('email', 'c@d.org')->unsubscribe_token;

    $this->getJson("/api/unsubscribe/{$token}")->assertOk()->assertJsonPath('status', 'unsubscribed');
    $this->getJson('/api/unsubscribe/bogus-token')->assertNotFound();

    expect(Subscriber::firstWhere('email', 'c@d.org')->unsubscribed_at)->not->toBeNull();
});

it('forbids guests and non-admins from broadcasting', function () {
    $this->postJson('/api/admin/notify-subscribers', ['subject' => 'x'])->assertUnauthorized();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/admin/notify-subscribers', ['subject' => 'x'])->assertForbidden();
});

it('lets an admin queue a broadcast to subscribers (fan-out is a job)', function () {
    Bus::fake();
    Subscriber::query()->create(['email' => 'live@x.org', 'unsubscribe_token' => str_repeat('a', 64), 'confirmed_at' => now()]);
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/admin/notify-subscribers', [
        'subject' => 'Нов скандал',
        'lines' => ['Виж новата публикация.'],
    ])->assertStatus(202)->assertJsonPath('recipients', 1);

    Bus::assertDispatched(NotifySubscribersJob::class);
});
