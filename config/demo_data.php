<?php

return [
    // Never enable this on a real customer database. Intended only for a disposable DEMO POS instance.
    // The artisan command also requires an explicit --confirm-demo and an untouched transaction history.
    'allow_history_seed' => env('DEMO_POS_ALLOW_HISTORY_SEED', false),
    'default_months' => 4,
    'default_orders_per_day' => 3,
    'default_seed' => 20261013,
    // These are fictitious recurring cash expenses, not computed COGS.
    'monthly_expenses' => [
        'Premises rental (sample)' => 3000,
        'Electricity and internet (sample)' => 1200,
        'Marketing (sample)' => 450,
    ],
];
