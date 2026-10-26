<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Display Timezone
    |--------------------------------------------------------------------------
    |
    | Timestamps are stored in UTC. This timezone is only used for grouping
    | and formatting user-facing dates (calendar days, daily reset, badges).
    |
    */

    'display_timezone' => env('TASKS_DISPLAY_TIMEZONE', 'Asia/Jakarta'),

    /*
    |--------------------------------------------------------------------------
    | Molor Thresholds
    |--------------------------------------------------------------------------
    |
    | A task is considered "molor" when it has been pushed back from the
    | today board (rollbacks) or re-entered today too many times.
    |
    */

    'molor' => [
        'today_threshold' => 3,
        'rollback_threshold' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | Stale Threshold
    |--------------------------------------------------------------------------
    |
    | Number of days without a status change before a backlog/todo task is
    | flagged as stale.
    |
    */

    'stale_after_days' => 14,

];
