<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\View\View;

/**
 * Base Controller.
 *
 * Provides common functionality for all CanvaStack controllers.
 * Includes traits for authorization, validation, and response helpers.
 */
abstract class Controller extends BaseController
{
    use AuthorizesRequests;
    use ValidatesRequests;
    use \Canvastack\Canvastack\Http\Traits\RespondsWithJson;
    use \Canvastack\Canvastack\Http\Traits\RespondsWithViews;
    use \Canvastack\Canvastack\Http\Traits\HandlesFlashMessages;

    /**
     * Success response helper.
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return JsonResponse
     */
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
    {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /**
     * Error response helper.
     *
     * @param string $message
     * @param mixed $errors
     * @param int $status
     * @return JsonResponse
     */
    protected function error(string $message = 'Error', mixed $errors = null, int $status = 400): JsonResponse
    {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }

    /**
     * Redirect with success message.
     *
     * @param string $route
     * @param string $message
     * @return RedirectResponse
     */
    protected function redirectWithSuccess(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('success', $message);
    }

    /**
     * Redirect with error message.
     *
     * @param string $route
     * @param string $message
     * @return RedirectResponse
     */
    protected function redirectWithError(string $route, string $message): RedirectResponse
    {
        return redirect()->route($route)->with('error', $message);
    }

    /**
     * Render view with data.
     *
     * @param string $view
     * @param array<string, mixed> $data
     * @return View
     */
    protected function render(string $view, array $data = []): View
    {
        return view($view, $data);
    }
}
