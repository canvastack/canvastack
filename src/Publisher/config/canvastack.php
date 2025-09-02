<?php

return [
    'routes' => [
        // Prefix for routes provided by the package
        'prefix' => env('CANVASTACK_ROUTE_PREFIX', 'canvastack'),
    ],

    // Merge nested configuration groups for the package
    'datatables' => include __DIR__.'/canvastack.datatables.php',
];
