<?php

declare(strict_types=1);

// Bulgarian-first, plain-language explanations a detector writes onto each flag
// (backend.md §10/§11). Placeholders are filled with the real numbers behind the claim.
return [
    'price_discrepancy' => 'Цената за „:product" (:price :currency) е :ratio× над типичната (:median :currency) за сравними поръчки.',
    'serial_winner' => '„:company" (ЕИК :eik) е спечелила :wins поръчки от :authorities възложителя.',
    'serial_winner_no_eik' => '„:company" е спечелила :wins поръчки от :authorities възложителя.',
    'cancelled' => 'Поръчката е със статус „:status" — обявена и след това прекратена от възложителя.',
];
