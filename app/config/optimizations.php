<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CSS Concatenation
    |--------------------------------------------------------------------------
    |
    | Enabling this option will concatenate all local CSS files enqueued via
    | wp_head into a single cached file, reducing HTTP requests.
    |
    */

    'css-concatenation' => [
        /**
         * Enable CSS concatenation.
         */
        'enabled' => true,

        /**
         * Handles to exclude from concatenation.
         */
        'exclude' => [
            // 'admin-bar',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | JS Concatenation
    |--------------------------------------------------------------------------
    |
    | Enabling this option will concatenate all local JS files enqueued via
    | wp_head into a single cached file, reducing HTTP requests.
    |
    */

    'js-concatenation' => [
        /**
         * Enable JS concatenation.
         */
        'enabled' => true,

        /**
         * Handles to exclude from concatenation.
         */
        'exclude' => [
            // 'jquery',
        ],
    ],

];
