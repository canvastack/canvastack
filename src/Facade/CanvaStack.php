<?php

namespace Canvastack\Canvastack\Facade;

use Illuminate\Support\Facades\Facade;

class CanvaStack extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'CanvaStack';
    }
}
