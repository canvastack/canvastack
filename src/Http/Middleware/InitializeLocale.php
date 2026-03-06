<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Middleware;

use Canvastack\Canvastack\Components\Table\Support\LocaleIntegration;
use Closure;
use Illuminate\Http\Request;

/**
 * InitializeLocale Middleware.
 *
 * Initializes locale from user preferences on each request.
 * Ensures user's preferred locale is loaded automatically.
 *
 * @package Canvastack\Canvastack\Http\Middleware
 * @version 1.0.0
 */
class InitializeLocale
{
    /**
     * Locale integration instance.
     */
    protected LocaleIntegration $localeIntegration;

    /**
     * Constructor.
     */
    public function __construct(LocaleIntegration $localeIntegration)
    {
        $this->localeIntegration = $localeIntegration;
    }

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Initialize locale from user preferences
        $this->localeIntegration->initializeFromPreferences();

        return $next($request);
    }
}
