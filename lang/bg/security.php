<?php

declare(strict_types=1);

return [
    // Deliberately generic — we never tell a banned caller WHY (security.md §9:
    // generic message to the client, full context only in the security log).
    'blacklisted' => 'Достъпът е отказан.',
];
