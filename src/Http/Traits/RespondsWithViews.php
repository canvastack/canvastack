<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Traits;

use Illuminate\View\View;

/**
 * Responds With Views Trait.
 *
 * Provides view response helpers for controllers.
 */
trait RespondsWithViews
{
    /**
     * Render an admin view.
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return View
     */
    protected function renderAdmin(string $view, array $data = []): View
    {
        return view("canvastack::admin.{$view}", $data);
    }

    /**
     * Render a public view.
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return View
     */
    protected function renderPublic(string $view, array $data = []): View
    {
        return view("canvastack::public.{$view}", $data);
    }

    /**
     * Render an auth view.
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return View
     */
    protected function renderAuth(string $view, array $data = []): View
    {
        return view("canvastack::auth.{$view}", $data);
    }

    /**
     * Render a component view.
     *
     * @param string $component
     * @param array<string, mixed> $data
     * @return View
     */
    protected function renderComponent(string $component, array $data = []): View
    {
        return view("canvastack::components.{$component}", $data);
    }

    /**
     * Share data with all views.
     *
     * @param string|array<string, mixed> $key
     * @param mixed $value
     * @return void
     */
    protected function shareWithViews(string|array $key, mixed $value = null): void
    {
        view()->share($key, $value);
    }
}
