<?php

namespace Canvastack\Canvastack\Library\Exceptions\Table;

/**
 * Exception thrown when a SQL injection attempt is detected
 * 
 * This exception is thrown when the Table Components system detects patterns
 * or inputs that indicate a potential SQL injection attack. This includes:
 * - Malicious SQL keywords in user input
 * - Attempts to manipulate table names
 * - Attempts to manipulate column names
 * - Suspicious operators or conditions
 * - Unvalidated raw SQL attempts
 * 
 * @package Canvastack\Canvastack\Library\Exceptions\Table
 * @author Canvastack Team
 * @since 1.0.0
 * 
 * @security CRITICAL - This exception indicates an active SQL injection attempt.
 *           All instances should be logged immediately and monitored for patterns.
 *           Consider implementing rate limiting or blocking for repeated attempts.
 * 
 * @example Detecting SQL injection in table name
 * ```php
 * public function setName(string $tableName): self
 * {
 *     // Validate table name format
 *     if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
 *         throw new SQLInjectionAttemptException(
 *             'Invalid table name format detected',
 *             0,
 *             null,
 *             [
 *                 'table_name' => $tableName,
 *                 'pattern' => 'Invalid characters detected',
 *                 'ip' => request()->ip()
 *             ]
 *         );
 *     }
 *     
 *     // Validate against whitelist
 *     if (!in_array($tableName, $this->allowedTables)) {
 *         throw new SQLInjectionAttemptException(
 *             'Table name not in whitelist',
 *             0,
 *             null,
 *             ['table_name' => $tableName]
 *         );
 *     }
 *     
 *     $this->tableName = $tableName;
 *     return $this;
 * }
 * ```
 * 
 * @example Detecting SQL injection in where clause
 * ```php
 * public function where(string $column, string $operator, mixed $value): self
 * {
 *     // Validate operator against whitelist
 *     $allowedOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'IN'];
 *     
 *     if (!in_array(strtoupper($operator), $allowedOperators)) {
 *         throw new SQLInjectionAttemptException(
 *             'Invalid SQL operator detected',
 *             0,
 *             null,
 *             [
 *                 'operator' => $operator,
 *                 'column' => $column,
 *                 'allowed_operators' => $allowedOperators
 *             ]
 *         );
 *     }
 *     
 *     // Add condition using parameter binding
 *     $this->conditions[] = [$column, $operator, $value];
 *     return $this;
 * }
 * ```
 * 
 * @example Handling SQL injection attempts
 * ```php
 * try {
 *     $table->setName($userInput);
 * } catch (SQLInjectionAttemptException $e) {
 *     // Log critical security incident
 *     Log::channel('security')->critical('SQL Injection attempt detected', [
 *         'message' => $e->getMessage(),
 *         'context' => $e->getContext(),
 *         'user_id' => auth()->id(),
 *         'ip' => request()->ip(),
 *         'user_agent' => request()->userAgent(),
 *         'timestamp' => now()
 *     ]);
 *     
 *     // Notify security team
 *     SecurityTeam::notifyInjectionAttempt($e);
 *     
 *     // Consider rate limiting or blocking
 *     RateLimiter::hit('sql-injection:' . request()->ip(), 3600);
 *     
 *     // Return generic error to user (don't reveal security details)
 *     abort(400, 'Invalid request');
 * }
 * ```
 */
class SQLInjectionAttemptException extends TableSecurityException
{
    /**
     * The type of SQL injection attempt detected
     * 
     * Possible values:
     * - 'table_name': Injection attempt in table name
     * - 'column_name': Injection attempt in column name
     * - 'operator': Invalid or malicious operator
     * - 'value': Suspicious value pattern
     * - 'raw_sql': Attempt to execute raw SQL
     * - 'order_by': Injection in ORDER BY clause
     * - 'where': Injection in WHERE clause
     *
     * @var string|null
     */
    protected ?string $injectionType = null;

    /**
     * The suspicious input that triggered the exception
     *
     * @var string|null
     */
    protected ?string $suspiciousInput = null;

    /**
     * Create a new SQLInjectionAttemptException instance
     * 
     * This exception is automatically set to 'critical' severity.
     *
     * @param string $message The exception message
     * @param int $code The exception code (default: 0)
     * @param \Exception|null $previous The previous exception for chaining
     * @param array $context Additional context data for debugging
     */
    public function __construct(
        string $message = "SQL injection attempt detected",
        int $code = 0,
        ?\Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous, $context);
        $this->severity = 'critical';
    }

    /**
     * Set the type of SQL injection attempt
     *
     * @param string $type The injection type
     * @return self
     */
    public function setInjectionType(string $type): self
    {
        $this->injectionType = $type;
        return $this;
    }

    /**
     * Get the type of SQL injection attempt
     *
     * @return string|null The injection type
     */
    public function getInjectionType(): ?string
    {
        return $this->injectionType;
    }

    /**
     * Set the suspicious input that triggered this exception
     *
     * @param string $input The suspicious input
     * @return self
     */
    public function setSuspiciousInput(string $input): self
    {
        $this->suspiciousInput = $input;
        return $this;
    }

    /**
     * Get the suspicious input that triggered this exception
     *
     * @return string|null The suspicious input
     */
    public function getSuspiciousInput(): ?string
    {
        return $this->suspiciousInput;
    }

    /**
     * Get a string representation with injection details
     *
     * @return string
     */
    public function __toString(): string
    {
        $string = "[SQL INJECTION ATTEMPT - CRITICAL] " . $this->getMessage();
        
        if ($this->injectionType) {
            $string .= "\nInjection Type: " . $this->injectionType;
        }
        
        if ($this->suspiciousInput) {
            $string .= "\nSuspicious Input: " . $this->suspiciousInput;
        }
        
        $string .= "\nFile: " . $this->getFile() . ":" . $this->getLine();
        
        if (!empty($this->context)) {
            $string .= "\nContext: " . json_encode($this->context, JSON_PRETTY_PRINT);
        }
        
        return $string;
    }
}
