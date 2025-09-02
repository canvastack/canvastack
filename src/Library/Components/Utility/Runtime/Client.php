<?php

namespace Canvastack\Canvastack\Library\Components\Utility\Runtime;

final class Client
{
    /** Get client IP address similar to legacy helper behavior. */
    public static function ip(): string
    {
        $server = $_SERVER ?? [];

        if (!empty($server['HTTP_CLIENT_IP'])) {
            return $server['HTTP_CLIENT_IP'];
        }
        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            return $server['HTTP_X_FORWARDED_FOR'];
        }
        if (!empty($server['HTTP_X_FORWARDED'])) {
            return $server['HTTP_X_FORWARDED'];
        }
        if (!empty($server['HTTP_FORWARDED_FOR'])) {
            return $server['HTTP_FORWARDED_FOR'];
        }
        if (!empty($server['HTTP_FORWARDED'])) {
            return $server['HTTP_FORWARDED'];
        }
        if (!empty($server['REMOTE_ADDR'])) {
            return $server['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $server['REMOTE_ADDR'];
        }
        return 'UNKNOWN';
    }
}