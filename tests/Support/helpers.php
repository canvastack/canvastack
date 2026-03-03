<?php

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder for tests.
     *
     * @param  string  $path
     * @return string
     */
    function storage_path($path = '')
    {
        $basePath = __DIR__ . '/../../storage';

        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources folder for tests.
     *
     * @param  string  $path
     * @return string
     */
    function resource_path($path = '')
    {
        $basePath = __DIR__ . '/../../resources';

        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('lang_path')) {
    /**
     * Get the path to the language folder for tests.
     *
     * @param  string  $path
     * @return string
     */
    function lang_path($path = '')
    {
        $basePath = __DIR__ . '/../../resources/lang';

        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the install for tests.
     *
     * @param  string  $path
     * @return string
     */
    function base_path($path = '')
    {
        $basePath = __DIR__ . '/../..';

        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the configuration path for tests.
     *
     * @param  string  $path
     * @return string
     */
    function config_path($path = '')
    {
        $basePath = __DIR__ . '/../../config';

        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder for tests.
     *
     * @param  string  $path
     * @return string
     */
    function public_path($path = '')
    {
        $basePath = __DIR__ . '/../../public';

        return $path ? $basePath . '/' . ltrim($path, '/') : $basePath;
    }
}

if (!function_exists('app')) {
    /**
     * Get the available container instance.
     *
     * @param  string|null  $abstract
     * @param  array  $parameters
     * @return mixed|\Illuminate\Container\Container
     */
    function app($abstract = null, array $parameters = [])
    {
        if (is_null($abstract)) {
            return \Illuminate\Container\Container::getInstance();
        }

        return \Illuminate\Container\Container::getInstance()->make($abstract, $parameters);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the auth manager instance.
     *
     * @param  string|null  $guard
     * @return mixed
     */
    function auth($guard = null)
    {
        if (is_null($guard)) {
            return \Illuminate\Container\Container::getInstance()->make('auth');
        }

        return \Illuminate\Container\Container::getInstance()->make('auth')->guard($guard);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the auth instance for tests.
     *
     * @return mixed
     */
    function auth()
    {
        return app('auth');
    }
}

if (!function_exists('session')) {
    /**
     * Get / set the specified session value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return mixed|\Illuminate\Session\Store|\Illuminate\Session\SessionManager
     */
    function session($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('session');
        }

        if (is_array($key)) {
            return app('session')->put($key);
        }

        return app('session')->get($key, $default);
    }
}

if (!function_exists('request')) {
    /**
     * Get an instance of the current request or an input item from the request.
     *
     * @param  array|string|null  $key
     * @param  mixed  $default
     * @return \Illuminate\Http\Request|string|array|null
     */
    function request($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('request');
        }

        if (is_array($key)) {
            return app('request')->only($key);
        }

        $value = app('request')->__get($key);

        return is_null($value) ? value($default) : $value;
    }
}

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
