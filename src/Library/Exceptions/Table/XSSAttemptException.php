<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when a Cross-Site Scripting (XSS) attempt is detected
 * 
 * This exception is thrown when the Table Components system detects patterns
 * or inputs that indicate a potential XSS attack. This includes:
 * - Script tags in user input
 * - JavaScript event handlers in attributes
 * - Malicious HTML in column labels or data
 * - Attempts to inject JavaScript code
 * - Suspicious HTML entities or encoding
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @security CRITICAL - This exception indicates an active XSS attempt.
 *           All instances should be logged immediately and monitored for patterns.
 *           Ensure all output is properly escaped before rendering.
 * 
 * @example Detecting XSS in column labels
 * ```php
 * public function setFields(array $fields): self
 * {
 *     foreach ($fields as $field => $label) {
 *         // Check for script tags
 *         if (preg_match('/<script\b[^>]*>/i', $label)) {
 *             throw new XSSAttemptException(
 *                 'Script tag detected in column label',
 *                 0,
 *                 null,
 *                 [
 *                     'field' => $field,
 *                     'label' => $label,
 *                     'pattern' => 'script_tag'
 *                 ]
 *             );
 *         }
 *         
 *         // Check for event handlers
 *         if (preg_match('/\bon\w+\s*=/i', $label)) {
 *             throw new XSSAttemptException(
 *                 'Event handler detected in column label',
 *                 0,
 *                 null,
 *                 [
 *                     'field' => $field,
 *                     'label' => $label,
 *                     'pattern' => 'event_handler'
 *                 ]
 *             );
 *         }
 *     }
 *     
 *     $this->fields = $fields;
 *     return $this;
 * }
 * ```
 * 
 * @example Detecting XSS in table attributes
 * ```php
 * public function setAttributes(array $attributes): self
 * {
 *     foreach ($attributes as $key => $value) {
 *         // Check for dangerous attributes
 *         if (preg_match('/^on/i', $key)) {
 *             throw new XSSAttemptException(
 *                 'Event handler attribute detected',
 *                 0,
 *                 null,
 *                 [
 *                     'attribute' => $key,
 *                     'value' => $value,
 *                     'pattern' => 'event_attribute'
 *                 ]
 *             );
 *         }
 *         
 *         // Check for javascript: protocol
 *         if (is_string($value) && preg_match('/javascript:/i', $value)) {
 *             throw new XSSAttemptException(
 *                 'JavaScript protocol detected in attribute',
 *                 0,
 *                 null,
 *                 [
 *                     'attribute' => $key,
 *                     'value' => $value,
 *                     'pattern' => 'javascript_protocol'
 *                 ]
 *             );
 *         }
 *     }
 *     
 *     $this->attributes = $attributes;
 *     return $this;
 * }
 * ```
 * 
 * @example Handling XSS attempts
 * ```php
 * try {
 *     $table->setFields($userFields);
 * } catch (XSSAttemptException $e) {
 *     // Log critical security incident
 *     Log::channel('security')->critical('XSS attempt detected', [
 *         'message' => $e->getMessage(),
 *         'context' => $e->getContext(),
 *         'attack_pattern' => $e->getAttackPattern(),
 *         'user_id' => auth()->id(),
 *         'ip' => request()->ip(),
 *         'timestamp' => now()
 *     ]);
 *     
 *     // Notify security team
 *     SecurityTeam::notifyXSSAttempt($e);
 *     
 *     // Return generic error (don't reveal security details)
 *     abort(400, 'Invalid input');
 * }
 * ```
 */
class XSSAttemptException extends TableSecurityException
{
    /**
     * The type of XSS attack pattern detected
     * 
     * Possible values:
     * - 'script_tag': <script> tag detected
     * - 'event_handler': JavaScript event handler (onclick, onload, etc.)
     * - 'javascript_protocol': javascript: protocol in URL
     * - 'html_injection': Malicious HTML tags
     * - 'attribute_injection': Injection in HTML attributes
     * - 'encoded_attack': Encoded XSS payload
     *
     * @var string|null
     */
    protected ?string $attackPattern = null;

    /**
     * The malicious input that triggered the exception
     *
     * @var string|null
     */
    protected ?string $maliciousInput = null;

    /**
     * The location where the XSS attempt was detected
     * 
     * Possible values:
     * - 'column_label': In column label
     * - 'table_attribute': In table attribute
     * - 'cell_data': In cell data
     * - 'action_button': In action button
     * - 'filter_value': In filter value
     *
     * @var string|null
     */
    protected ?string $location = null;

    /**
     * Create a new XSSAttemptException instance
     * 
     * This exception is automatically set to 'critical' severity.
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "XSS attempt detected",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->severity = 'critical';
    }

    /**
     * Set the attack pattern type
     *
     * @param string $pattern The attack pattern
     * @return self
     */
    public function setAttackPattern(string $pattern): self
    {
        $this->attackPattern = $pattern;
        return $this;
    }

    /**
     * Get the attack pattern type
     *
     * @return string|null The attack pattern
     */
    public function getAttackPattern(): ?string
    {
        return $this->attackPattern;
    }

    /**
     * Set the malicious input that triggered this exception
     *
     * @param string $input The malicious input
     * @return self
     */
    public function setMaliciousInput(string $input): self
    {
        $this->maliciousInput = $input;
        return $this;
    }

    /**
     * Get the malicious input that triggered this exception
     *
     * @return string|null The malicious input
     */
    public function getMaliciousInput(): ?string
    {
        return $this->maliciousInput;
    }

    /**
     * Set the location where the XSS attempt was detected
     *
     * @param string $location The location
     * @return self
     */
    public function setLocation(string $location): self
    {
        $this->location = $location;
        return $this;
    }

    /**
     * Get the location where the XSS attempt was detected
     *
     * @return string|null The location
     */
    public function getLocation(): ?string
    {
        return $this->location;
    }

    /**
     * Get a string representation with XSS attack details
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = "[XSS ATTEMPT - CRITICAL] " . $this->getMessage();
        
        if ($this->attackPattern) {
            $string .= "\nAttack Pattern: " . $this->attackPattern;
        }
        
        if ($this->location) {
            $string .= "\nLocation: " . $this->location;
        }
        
        if ($this->maliciousInput) {
            // Truncate long inputs for readability
            $input = strlen($this->maliciousInput) > 100 
                ? substr($this->maliciousInput, 0, 100) . '...' 
                : $this->maliciousInput;
            $string .= "\nMalicious Input: " . $input;
        }
        
        $string .= "\nFile: " . $this->getFile() . ":" . $this->getLine();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
