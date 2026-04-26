<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when a security issue is detected in Table Components
 * 
 * This exception is the base class for all security-related exceptions in the
 * Table Components system. It covers issues such as:
 * - SQL injection attempts
 * - XSS (Cross-Site Scripting) attempts
 * - Invalid table name access
 * - Unauthorized data access
 * - Malicious input patterns
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @security This exception indicates a potential security threat and should be
 *           logged with high priority for security monitoring and incident response.
 * 
 * @example Catching security exceptions
 * ```php
 * try {
 *     $table->setName($userInput);
 * } catch (TableSecurityException $e) {
 *     // Log security incident
 *     Log::critical('Security threat detected', [
 *         'message' => $e->getMessage(),
 *         'context' => $e->getContext(),
 *         'ip' => request()->ip(),
 *         'user' => auth()->id()
 *     ]);
 *     
 *     // Return safe error to user
 *     return response()->json([
 *         'error' => 'Invalid request'
 *     ], 400);
 * }
 * ```
 * 
 * @example Monitoring security exceptions
 * ```php
 * // In your exception handler
 * public function report(Throwable $exception)
 * {
 *     if ($exception instanceof TableSecurityException) {
 *         // Send alert to security team
 *         SecurityMonitor::alert($exception);
 *         
 *         // Log with full context
 *         Log::channel('security')->critical($exception->getMessage(), [
 *             'exception' => get_class($exception),
 *             'context' => $exception->getContext(),
 *             'trace' => $exception->getTraceAsString()
 *         ]);
 *     }
 * }
 * ```
 */
class TableSecurityException extends TableComponentException
{
    /**
     * The severity level of the security issue
     * 
     * Possible values:
     * - 'critical': Immediate threat requiring urgent action
     * - 'high': Serious security issue requiring prompt attention
     * - 'medium': Moderate security concern
     * - 'low': Minor security issue or suspicious activity
     *
     * @var string
     */
    protected string $severity = 'high';

    /**
     * Get the severity level of this security exception
     *
     * @return string The severity level (critical, high, medium, low)
     * 
     * @example
     * ```php
     * try {
     *     $table->where($column, $operator, $value);
     * } catch (TableSecurityException $e) {
     *     if ($e->getSeverity() === 'critical') {
     *         // Immediate action required
     *         SecurityTeam::notifyImmediate($e);
     *     }
     * }
     * ```
     */
    public function getSeverity(): string
    {
        return $this->severity;
    }

    /**
     * Set the severity level of this security exception
     *
     * @param string $severity The severity level (critical, high, medium, low)
     * @return self
     * 
     * @example
     * ```php
     * $exception = new TableSecurityException('Suspicious input detected');
     * $exception->setSeverity('critical')
     *           ->addContext('input', $userInput);
     * throw $exception;
     * ```
     */
    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    /**
     * Check if this is a critical security issue
     *
     * @return bool True if severity is critical
     */
    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    /**
     * Get a string representation with severity information
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = "[SECURITY - {$this->severity}] " . $this->getMessage();
        $string .= "\nFile: " . $this->getFile() . ":" . $this->getLine();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
