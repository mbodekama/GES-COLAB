<?php

return [

    // ── Entreprise ────────────────────────────────────────────
    'company_name'     => env('COMPANY_NAME', 'GES-COLAB'),
    'company_initials' => env('COMPANY_INITIALS', ''),
    'company_address'  => env('COMPANY_ADDRESS', 'Abidjan, Côte d\'Ivoire'),
    'company_phone'    => env('COMPANY_PHONE', ''),
    'company_email'    => env('COMPANY_EMAIL', ''),
    'company_website'  => env('COMPANY_WEBSITE', ''),

    // ── Application ───────────────────────────────────────────
    'default_language'      => env('APP_LOCALE', 'fr'),
    'currency'              => env('GESCOLAB_CURRENCY', 'FCFA'),
    'working_days_per_week' => env('GESCOLAB_WORKING_DAYS', 5),

    // ── Paie ─────────────────────────────────────────────────
    'cnps_employer_rate'  => env('GESCOLAB_CNPS_EMPLOYER_RATE', 12),
    'cnps_employee_rate'  => env('GESCOLAB_CNPS_EMPLOYEE_RATE', 6.3),
    'transport_allowance' => env('GESCOLAB_TRANSPORT_ALLOWANCE', 30000),
    'housing_allowance'   => env('GESCOLAB_HOUSING_ALLOWANCE', 25000),
    'meal_allowance'      => env('GESCOLAB_MEAL_ALLOWANCE', 0),
    'payroll_day'         => env('GESCOLAB_PAYROLL_DAY', 25),

    // ── Congés ────────────────────────────────────────────────
    'annual_leave_days'        => env('GESCOLAB_ANNUAL_LEAVE_DAYS', 30),
    'sick_leave_days'          => env('GESCOLAB_SICK_LEAVE_DAYS', 15),
    'exceptional_leave_days'   => env('GESCOLAB_EXCEPTIONAL_LEAVE_DAYS', 5),
    'max_permission_per_month' => env('GESCOLAB_MAX_PERMISSION_PER_MONTH', 2),

];
