<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Gig rules
    |--------------------------------------------------------------------------
    |
    | Business rules from the system specification. Values that are still
    | pending a decision can be changed in .env without touching any code.
    |
    */

    'currency' => 'RM',

    // A job may last at most this many days, counting both start and end date.
    'max_job_days' => 30,

    // A pending submission is approved automatically after this many days
    // without a response from the Contract Entity. Spec section 5.
    'auto_approve_days' => (int) env('GIG_AUTO_APPROVE_DAYS', 7),

    // A listing is hidden once this many distinct users report it. Section 8.
    'report_threshold' => (int) env('GIG_REPORT_THRESHOLD', 3),

    // One reminder email goes out this many days after the worker finishes
    // the job, if payment has not been confirmed yet. Section 11.
    'payment_reminder_days' => (int) env('GIG_PAYMENT_REMINDER_DAYS', 3),

    // Work submission uploads. Section 5.
    'submissions' => [
        'max_files' => 5,
        'max_file_kb' => 10240,
        'extensions' => ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'zip'],
    ],

];
