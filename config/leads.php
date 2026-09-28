<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Business Time Zone
    |--------------------------------------------------------------------------
    |
    | A buyer's daily cap counts the leads delivered since midnight in this
    | time zone, which is where the business keeps its books, not where
    | the server happens to run.
    |
    */

    'business_timezone' => env('LEADS_BUSINESS_TIMEZONE', 'America/New_York'),

];
