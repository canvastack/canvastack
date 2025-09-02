<?php

use Illuminate\Support\Facades\Route as RouteFacade;

if (! function_exists('current_route')) {
    /** Get current route name safely */
    function current_route()
    {
        return RouteFacade::currentRouteName();
    }
}

if (! function_exists('canvastack_memory')) {
    /** Optionally increase memory/time limits (delegated to Canvatility) */
    function canvastack_memory($enable = false)
    {
        return \Canvastack\Canvastack\Library\Components\Utility\Canvatility::memory((bool)$enable);
    }
}
