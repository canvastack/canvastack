<?php
namespace Canvastack\Canvastack\Exceptions\Controller;

/**
 * CSRF Exception
 * 
 * بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ
 * 
 * Exception thrown when CSRF token validation fails.
 * This indicates a potential Cross-Site Request Forgery attack.
 * 
 * @package Canvastack\Canvastack\Exceptions\Controller
 * @category Security
 * @version 1.0.0
 * 
 * @security CRITICAL - This exception indicates a security violation
 *           All CSRF failures should be logged and monitored
 * 
 * @example
 * ```php
 * // In controller action
 * if (!$this->validateCsrfToken($request)) {
 *     throw new CSRFException(
 *         'CSRF token mismatch',
 *         [
 *             'expected' => session('_token'),
 *             'received' => $request->input('_token'),
 *             'route' => $request->path()
 *         ]
 *     );
 * }
 * ```
 */
class CSRFException extends ControllerSecurityException
{
    /**
     * Constructor
     * 
     * @param string $message Technical error message for logging
     * @param array $context Additional context data including token details
     * @param int $code HTTP status code (default: 419 Page Expired)
     * @param \Exception|null $previous Previous exception in the chain
     */
    public function __construct(
        string $message = 'CSRF token validation failed',
        array $context = [],
        int $code = 419,
        ?\Exception $previous = null
    ) {
        parent::__construct($message, $context, $code, $previous);
        $this->threatType = 'csrf';
        $this->userMessage = 'Your request could not be processed due to a security check failure. Please refresh the page and try again.';
    }
    
    /**
     * Get CSRF violation details
     * 
     * Returns specific details about the CSRF token validation failure.
     * 
     * @return array CSRF violation details
     */
    public function getCsrfDetails(): array
    {
        return [
            'expected_token' => $this->context['expected'] ?? 'not_set',
            'received_token' => $this->context['received'] ?? 'not_provided',
            'route' => $this->context['route'] ?? 'unknown',
            'method' => $this->context['method'] ?? 'unknown',
            'referer' => $this->context['referer'] ?? 'unknown',
        ];
    }
}
