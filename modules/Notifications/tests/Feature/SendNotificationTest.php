<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Mail;
use Modules\Notifications\Actions\SendNotificationAction;
use Modules\Notifications\Mail\GenericNotificationMail;

it('queues the notification mail instead of sending inline', function () {
    Mail::fake();

    app(SendNotificationAction::class)->execute(
        'someone@example.org',
        'Тест',
        ['ред едно', 'ред две'],
    );

    Mail::assertQueued(
        GenericNotificationMail::class,
        fn (GenericNotificationMail $mail) => $mail->hasTo('someone@example.org')
            && $mail->subjectLine === 'Тест',
    );
    Mail::assertNotSent(GenericNotificationMail::class);
});

it('is a queued mailable (ShouldQueue)', function () {
    expect(new GenericNotificationMail('x'))
        ->toBeInstanceOf(Illuminate\Contracts\Queue\ShouldQueue::class);
});
