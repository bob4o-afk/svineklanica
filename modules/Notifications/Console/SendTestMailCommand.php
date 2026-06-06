<?php

declare(strict_types=1);

namespace Modules\Notifications\Console;

use Illuminate\Console\Command;
use Modules\Notifications\Actions\SendNotificationAction;

/** Queues a test notification through the configured mailer (Resend). */
final class SendTestMailCommand extends Command
{
    protected $signature = 'mail:test {email}';

    protected $description = 'Queue a test notification email to the given address.';

    public function handle(SendNotificationAction $send): int
    {
        $email = (string) $this->argument('email');

        $send->execute($email, 'Свинекланица Watchdog — тест', [
            'Това е тестово известие, изпратено през опашката с Resend.',
            'Ако го виждаш, queued mail-ът работи.',
        ]);

        $this->info("Queued test mail to {$email}. Process it with: php artisan queue:work --once");

        return self::SUCCESS;
    }
}
