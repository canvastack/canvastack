<?php

namespace Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Support;

class RequestInput
{
    /** Aggregate selected inputs safely (skeleton). */
    public static function fromGlobals(): array
    {
        // Support both Laravel request() and plain PHP unit env
        $start = 0;
        $length = 10;
        if (function_exists('request')) {
            try {
                $req = request();
                if ($req) {
                    $start = (int) $req->get('start', 0);
                    $length = (int) $req->get('length', 10);
                }
            } catch (\Throwable $e) {
                // ignore fallback below
            }
        }
        if (! function_exists('request')) {
            $start = isset($_GET['start']) ? (int) $_GET['start'] : 0;
            $length = isset($_GET['length']) ? (int) $_GET['length'] : 10;
        }

        return [
            'start' => $start,
            'length' => $length,
        ];
    }
}
