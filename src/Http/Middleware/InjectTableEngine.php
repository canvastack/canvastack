<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * InjectTableEngine Middleware.
 *
 * Reads table engine from request and shares it with all views.
 * This ensures the variable is available BEFORE view rendering starts.
 */
class InjectTableEngine
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
        // Check if table engine is set in request (set by controller)
        if ($request->has('_table_engine')) {
            $engine = $request->input('_table_engine');
            
            // Set in config for backward compatibility
            config(['canvastack.current_table_engine' => $engine]);
            
            \Log::info('InjectTableEngine: Set table_engine = ' . $engine);
        } else {
            // Default to 'datatables' if not set
            $engine = 'datatables';
            $request->merge(['_table_engine' => $engine]);
            config(['canvastack.current_table_engine' => $engine]);
            
            \Log::info('InjectTableEngine: Default table_engine = ' . $engine);
        }

        return $next($request);
    }
}
