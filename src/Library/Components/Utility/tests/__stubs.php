<?php

if (!function_exists('session')) {
    function session() {
        return new class {
            public function all() {
                return [
                    'privileges' => [
                        'role' => [],
                    ],
                ];
            }
        };
    }
}

if (!function_exists('current_route')) {
    function current_route() {
        return 'dummy@route';
    }
}

if (!function_exists('routelists_info')) {
    function routelists_info($route = null) {
        return [
            'base_info' => 'dummy',
            'last_info' => 'index',
        ];
    }
}

if (!function_exists('canvastack_string_contained')) {
    function canvastack_string_contained($haystack, $needle) {
        return is_string($haystack) && strpos($haystack, (string) $needle) !== false;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() { return ''; }
}

if (!function_exists('action')) {
    function action($name, $id) { return '/delete/'.$id; }
}

if (!function_exists('camel_case')) {
    function camel_case($str) { return $str; }
}