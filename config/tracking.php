<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Visit Retention
    |--------------------------------------------------------------------------
    |
    | How many days of tracked page visits to keep. The `visits:prune` command
    | (scheduled weekly) deletes rows older than this. Set to 0 to disable
    | pruning and retain visits indefinitely.
    |
    */

    'retention_days' => (int) env('VISIT_RETENTION_DAYS', 90),

];
