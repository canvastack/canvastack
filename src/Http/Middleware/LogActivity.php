<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Middleware;

use Canvastack\Canvastack\Support\ActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Log Activity Middleware.
 *
 * Automatically logs user activities for auditing and monitoring.
 */
class LogActivity
{
    /**
     * Activity logger instance.
     *
     * @var ActivityLogger
     */
    protected ActivityLogger $logger;

    /**
     * Constructor.
     *
     * @param ActivityLogger $logger
     */
    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Reset timer for this request
        $this->logger->resetTimer();

        // Process the request
        $response = $next($request);

        // Log the activity after response
        $this->logActivity($request, $response);

        return $response;
    }

    /**
     * Log the activity.
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    protected function logActivity(Request $request, Response $response): void
    {
        // Determine status based on response
        $status = $this->determineStatus($response);

        // Log the request
        $this->logger->logRequest(
            $request,
            null,
            null,
            $status
        );
    }

    /**
     * Determine status from response.
     *
     * @param Response $response
     * @return string
     */
    protected function determineStatus(Response $response): string
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 200 && $statusCode < 300) {
            return 'success';
        }

        if ($statusCode >= 400 && $statusCode < 500) {
            return 'failed';
        }

        if ($statusCode >= 500) {
            return 'error';
        }

        return 'success';
    }
}
