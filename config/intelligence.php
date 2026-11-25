<?php

return [
    'enabled' => env('AI_ENABLED', false),
    'model' => env('GEMINI_MODEL', 'gemini-3.5-flash-lite'),
    'daily_limit' => (int) env('AI_DAILY_LIMIT', 30),
    'timeout' => 40,
    'minimum_transactions' => 10,
];
