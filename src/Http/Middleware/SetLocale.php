<?php

namespace Canvastack\Canvastack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetLocale Middleware.
 *
 * Detects and sets the application locale based on various sources.
 * Priority: URL > Session > Cookie > Browser > Default
 */
class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $localeManager = app('canvastack.locale');

        // Locale is already detected in LocaleManager constructor
        // But we can override it here if needed (e.g., from URL parameter)
        if ($request->has('locale')) {
            $locale = $request->input('locale');
            if ($localeManager->isAvailable($locale)) {
                $localeManager->setLocale($locale);
            }
        }

        // Set HTML lang attribute
        $request->attributes->set('locale', $localeManager->getLocale());

        // Set text direction for RTL languages
        $request->attributes->set('direction', $localeManager->getDirection());

        return $next($request);
    }
}
