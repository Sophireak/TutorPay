<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Symbol prefixed to every amount rendered by the <x-money> component.
    |
    */

    'currency_symbol' => env('TUTORPAY_CURRENCY_SYMBOL', '$'),

    /*
    |--------------------------------------------------------------------------
    | Default Due Day
    |--------------------------------------------------------------------------
    |
    | Day of the month pre-selected when generating monthly fees.
    |
    */

    'default_due_day' => (int) env('TUTORPAY_DEFAULT_DUE_DAY', 10),

];
