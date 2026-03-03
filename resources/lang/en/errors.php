<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Error Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for error messages and HTTP
    | status codes throughout the application.
    |
    */

    'http' => [
        '400' => 'Bad Request',
        '401' => 'Unauthorized',
        '403' => 'Forbidden',
        '404' => 'Not Found',
        '405' => 'Method Not Allowed',
        '408' => 'Request Timeout',
        '419' => 'Page Expired',
        '429' => 'Too Many Requests',
        '500' => 'Internal Server Error',
        '502' => 'Bad Gateway',
        '503' => 'Service Unavailable',
        '504' => 'Gateway Timeout',
    ],

    'messages' => [
        '400' => 'The request could not be understood by the server.',
        '401' => 'You are not authorized to access this resource.',
        '403' => 'You do not have permission to access this resource.',
        '404' => 'The page you are looking for could not be found.',
        '405' => 'The method is not allowed for this resource.',
        '408' => 'The request took too long to process.',
        '419' => 'Your session has expired. Please refresh and try again.',
        '429' => 'Too many requests. Please slow down.',
        '500' => 'Something went wrong on our end. Please try again later.',
        '502' => 'Bad gateway. Please try again later.',
        '503' => 'The service is temporarily unavailable. Please try again later.',
        '504' => 'Gateway timeout. Please try again later.',
    ],

    'actions' => [
        'go_home' => 'Go to Homepage',
        'go_back' => 'Go Back',
        'try_again' => 'Try Again',
        'contact_support' => 'Contact Support',
        'refresh' => 'Refresh Page',
    ],

    'general' => [
        'error' => 'Error',
        'oops' => 'Oops!',
        'something_wrong' => 'Something went wrong',
        'try_again' => 'Please try again',
        'contact_admin' => 'If the problem persists, please contact the administrator.',
    ],

    'validation' => [
        'failed' => 'Validation failed',
        'check_input' => 'Please check your input and try again.',
    ],

    'database' => [
        'connection_failed' => 'Database connection failed',
        'query_failed' => 'Database query failed',
    ],

    'file' => [
        'not_found' => 'File not found',
        'upload_failed' => 'File upload failed',
        'invalid_type' => 'Invalid file type',
        'too_large' => 'File is too large',
    ],

    'permission' => [
        'denied' => 'Permission denied',
        'insufficient' => 'You do not have sufficient permissions',
    ],
];
