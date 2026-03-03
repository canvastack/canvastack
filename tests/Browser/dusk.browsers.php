<?php

/**
 * Browser Configuration for Cross-Browser Testing.
 *
 * This file defines browser configurations for Laravel Dusk tests.
 * Uncomment the browsers you want to test against.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Chrome Browser
    |--------------------------------------------------------------------------
    */
    'chrome' => [
        'driver' => 'chrome',
        'capabilities' => [
            'goog:chromeOptions' => [
                'args' => [
                    '--disable-gpu',
                    '--headless',
                    '--window-size=1920,1080',
                    '--no-sandbox',
                    '--disable-dev-shm-usage',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Firefox Browser
    |--------------------------------------------------------------------------
    */
    'firefox' => [
        'driver' => 'firefox',
        'capabilities' => [
            'moz:firefoxOptions' => [
                'args' => [
                    '-headless',
                    '-width=1920',
                    '-height=1080',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Edge Browser
    |--------------------------------------------------------------------------
    */
    'edge' => [
        'driver' => 'edge',
        'capabilities' => [
            'ms:edgeOptions' => [
                'args' => [
                    '--headless',
                    '--window-size=1920,1080',
                    '--no-sandbox',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Safari Browser (macOS only)
    |--------------------------------------------------------------------------
    */
    'safari' => [
        'driver' => 'safari',
        'capabilities' => [],
    ],
];
