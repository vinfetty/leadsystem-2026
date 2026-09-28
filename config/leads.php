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

    /*
    |--------------------------------------------------------------------------
    | Demo Mode
    |--------------------------------------------------------------------------
    |
    | In demo mode anyone may sign in as one of the seeded accounts with a
    | single click, every page says the data is invented, search engines
    | are asked to stay away, and the database is rebuilt every hour.
    | Leave this off for anything that holds real leads.
    |
    */

    'demo' => (bool) env('DEMO_MODE', false),

];
