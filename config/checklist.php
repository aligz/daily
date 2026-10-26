<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Routine Limit
    |--------------------------------------------------------------------------
    |
    | Soft cap per user so the checklist bar stays readable and the per-day
    | calculations stay cheap. Enforced when creating a routine.
    |
    */

    'max_routines' => 20,

    /*
    |--------------------------------------------------------------------------
    | Note Length
    |--------------------------------------------------------------------------
    |
    | Maximum characters for the single daily note.
    |
    */

    'note_max_length' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Streak
    |--------------------------------------------------------------------------
    |
    | How many days back the streak calculation looks. A day counts when every
    | routine that was active that day is done or skipped.
    |
    */

    'streak_lookback_days' => 90,

];
