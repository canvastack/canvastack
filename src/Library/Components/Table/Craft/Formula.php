<?php
namespace Canvastack\Canvastack\Library\Components\Table\Craft;

use Canvastack\Canvastack\Library\Constants\TableConstants;
use Canvastack\Canvastack\Library\Exceptions\Table\FormulaException;
use jlawrence\eos\Parser;
/**
 * Created on 11 Jun 2021
 * Time Created	: 15:58:07
 *
 * @filesource	Formula.php
 *
 * @author		wisnuwidi@canvastack.com - 2021
 * @copyright	wisnuwidi
 * @email		wisnuwidi@canvastack.com
 *
 * @performance Optimized with instance-level result caching, lazy header evaluation,
 *              short-circuit for trivial formulas, and static performance metrics.
 */

class Formula extends Parser {

	/**
	 * Whitelist of allowed operators for formula expressions.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_OPERATORS = [
		'+', '-', '*', '/', '%', '^', '(', ')', ' ',
		'>', '<', '>=', '<=', '==', '!=', '&&', '||', '!',
	];

	/**
	 * Whitelist of allowed functions for formula expressions.
	 *
	 * @var array<string>
	 */
	private const ALLOWED_FUNCTIONS = [
		'abs', 'ceil', 'floor', 'round', 'sqrt', 'pow', 'exp', 'log', 'log10',
		'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'min', 'max',
		'if', 'and', 'or', 'not',
		'concat', 'substring', 'uppercase', 'lowercase', 'length', 'trim',
	];

	/**
	 * Maximum allowed formula length to prevent DoS attacks.
	 *
	 * @var int
	 */
	private const MAX_FORMULA_LENGTH = 1000;

	/**
	 * Maximum allowed nesting depth for parentheses.
	 *
	 * @var int
	 */
	private const MAX_NESTING_DEPTH = 10;

	private $data    = [];
	private $headers = [];
	private $formula = null;

	/**
	 * Instance-level cache: keyed by hash of formula logic + row values.
	 * Instance-level (not static) to avoid cross-request contamination.
	 *
	 * @var array<string, float|int>
	 */
	private array $resultCache = [];

	/**
	 * Tracks the formula logic string for which $this->headers was last built,
	 * enabling lazy re-evaluation only when the formula changes.
	 *
	 * @var string|null
	 */
	private ?string $lastHeaderFormula = null;

	/**
	 * Aggregate performance metrics (static — monitoring data, not per-request state).
	 *
	 * @var array{call_count: int, cache_hits: int, cache_misses: int, total_time_ms: float}
	 */
	private static array $metrics = [
		'call_count'    => 0,
		'cache_hits'    => 0,
		'cache_misses'  => 0,
		'total_time_ms' => 0.0,
	];

	public function __construct(array $data_formula, mixed $data_query) {
		$data = [];
		$data['formula']    = $data_formula;
		$data['query_data'] = $data_query->getAttributes();
		$this->data	        = canvastack_object($data);
	}

	/**
	 * Calculate the formula value for the current row.
	 *
	 * Applies instance-level caching, lazy header sanitization, and performance
	 * monitoring. The public signature is unchanged for full backward compatibility.
	 *
	 * @return float|int|string Calculated formula result (returns 0 or empty string on error)
	 *
	 * @performance Uses instance-level caching and lazy evaluation
	 * @security Validates formula syntax to prevent code injection
	 */
	public function calculate() {
		self::$metrics['call_count']++;

		$formula    = $this->data->formula['logic'];
		$column_key = $this->data->formula['name'];
		$row        = $this->data->query_data;

		// Validate formula syntax on first use
		if ($this->lastHeaderFormula !== $formula) {
			try {
				$this->validateFormulaSyntax($formula);
				// SECURITY: Validate formula expression for dangerous functions
				$this->validateFormulaExpression($formula);
			} catch (\InvalidArgumentException $e) {
				// Log validation error with context
				$this->logFormulaError('validation', $e->getMessage(), [
					'formula' => $formula,
					'column' => $column_key,
				]);
				$this->formula = 0;
				return $this->formula;
			}
		}

		// Build a cache key from the formula logic and the relevant row values.
		$cacheKey = $this->buildCacheKey($formula, $this->data->formula['field_lists'], $row);

		// L1: Instance-level cache (fastest, per-request)
		if (isset($this->resultCache[$cacheKey])) {
			// Cache hit — skip computation entirely.
			self::$metrics['cache_hits']++;
			$this->formula = $this->resultCache[$cacheKey];
			return $this->formula;
		}

		// L2: Persistent cache (CONFIG: formula.cache_results)
		$persistentCacheEnabled = config('canvastack.cache.formula_results.enabled', true);
		if ($persistentCacheEnabled) {
			$persistentCacheKey = config('canvastack.cache.prefix', 'canvastack_') . 
								  config('canvastack.cache.formula_results.key_prefix', 'formula_results_') . 
								  $cacheKey;
			$cacheTtl = config('canvastack.cache.formula_results.ttl', 300);
			
			$cached = \Illuminate\Support\Facades\Cache::get($persistentCacheKey);
			if ($cached !== null) {
				// L2 cache hit — restore to L1 and return
				self::$metrics['cache_hits']++;
				$this->resultCache[$cacheKey] = $cached;
				$this->formula = $cached;
				
				// Monitor cache hit
				canvastack_table_cache_monitor('get', $persistentCacheKey, true);
				
				return $this->formula;
			}
			
			// Monitor cache miss
			canvastack_table_cache_monitor('get', $persistentCacheKey, false);
		}

		// Cache miss — compute the result and track time.
		self::$metrics['cache_misses']++;
		$startTime = microtime(true);

		// Lazy evaluation: only rebuild headers when the formula logic changes.
		if ($this->lastHeaderFormula !== $formula) {
			$this->header_sanitizer($this->data->formula['field_lists']);
			$this->lastHeaderFormula = $formula;
		}

		try {
			$row[$column_key] = $this->parsing($formula, $this->headers, $row);
		} catch (\DivisionByZeroError $e) {
			$this->logFormulaError('division_by_zero', 'Division by zero in formula', [
				'formula' => $formula,
				'column' => $column_key,
				'row_data' => $this->sanitizeRowDataForLog($row),
			]);
			$row[$column_key] = 0;
		} catch (\ArithmeticError $e) {
			$this->logFormulaError('arithmetic_error', $e->getMessage(), [
				'formula' => $formula,
				'column' => $column_key,
				'row_data' => $this->sanitizeRowDataForLog($row),
			]);
			$row[$column_key] = 0;
		} catch (\Exception $e) {
			$this->logFormulaError('evaluation_error', $e->getMessage(), [
				'formula' => $formula,
				'column' => $column_key,
				'error_type' => get_class($e),
			]);
			$row[$column_key] = 0;
		}

		$elapsed = (microtime(true) - $startTime) * 1000;
		self::$metrics['total_time_ms'] += $elapsed;

		$this->formula = $row[$column_key];

		// Store in L1 instance cache
		$this->resultCache[$cacheKey] = $this->formula;

		// Store in L2 persistent cache (CONFIG: formula.cache_results)
		if ($persistentCacheEnabled) {
			\Illuminate\Support\Facades\Cache::put($persistentCacheKey, $this->formula, $cacheTtl);
			canvastack_table_cache_monitor('put', $persistentCacheKey, true);
		}

		return $this->formula;
	}
	/**
	 * Validate formula expression for security
	 *
	 * SECURITY: Validates formula functions against whitelist to prevent code injection
	 *
	 * @param string $formula Formula expression to validate
	 * @return void
	 * @throws \InvalidArgumentException If formula contains disallowed functions
	 */
	private function validateFormulaExpression(string $formula): void
	{
	    if (!config('canvastack.datatables.formula.validate_expressions', true)) {
	        return;
	    }

	    // Get allowed functions from config
	    $allowedFunctions = config('canvastack.datatables.formula.allowed_functions', [
	        // Mathematical
	        'abs', 'ceil', 'floor', 'round', 'sqrt', 'exp', 'log', 'log10', 'pow',
	        'sin', 'cos', 'tan', 'asin', 'acos', 'atan', 'min', 'max', 'sum', 'avg',
	        // String
	        'concat', 'substring', 'uppercase', 'lowercase', 'length', 'trim',
	        // Conditional
	        'if', 'and', 'or', 'not',
	    ]);

	    // Extract all function calls from formula
	    preg_match_all('/\b([a-z_][a-z0-9_]*)\s*\(/i', $formula, $matches);

	    if (!empty($matches[1])) {
	        foreach ($matches[1] as $functionName) {
	            $funcLower = strtolower($functionName);

	            if (!in_array($funcLower, $allowedFunctions, true)) {
	                // Log security event
	                if (config('canvastack.datatables.security.log_security_events', true)) {
	                    canvastack_table_log_security_event('invalid_formula_function', [
	                        'function' => $functionName,
	                        'formula' => $formula,
	                        'allowed' => $allowedFunctions,
	                    ]);
	                }

	                throw new \InvalidArgumentException("Disallowed function in formula: {$functionName}");
	            }
	        }
	    }

	    // Additional security checks
	    $dangerousPatterns = [
	        '/\beval\s*\(/i' => 'eval() function',
	        '/\bexec\s*\(/i' => 'exec() function',
	        '/\bsystem\s*\(/i' => 'system() function',
	        '/\bshell_exec\s*\(/i' => 'shell_exec() function',
	        '/\bpassthru\s*\(/i' => 'passthru() function',
	        '/`[^`]*`/' => 'backtick operator',
	        '/\$\{[^}]*\}/' => 'variable interpolation',
	    ];

	    foreach ($dangerousPatterns as $pattern => $description) {
	        if (preg_match($pattern, $formula)) {
	            if (config('canvastack.datatables.security.log_security_events', true)) {
	                canvastack_table_log_security_event('dangerous_formula_pattern', [
	                    'pattern' => $description,
	                    'formula' => $formula,
	                ]);
	            }

	            throw new \InvalidArgumentException("Dangerous pattern detected in formula: {$description}");
	        }
	    }
	}

	/**
	 * Log formula errors with context.
	 *
	 * @param string $errorType Type of error (validation, division_by_zero, etc.)
	 * @param string $message Error message
	 * @param array $context Additional context data
	 * @return void
	 */
	private function logFormulaError(string $errorType, string $message, array $context = []): void {
		$logMessage = sprintf(
			'[Formula Error] Type: %s, Message: %s, Context: %s',
			$errorType,
			$message,
			json_encode($context)
		);
		
		error_log($logMessage);
		
		// Track error metrics
		if (!isset(self::$metrics['errors'])) {
			self::$metrics['errors'] = [];
		}
		if (!isset(self::$metrics['errors'][$errorType])) {
			self::$metrics['errors'][$errorType] = 0;
		}
		self::$metrics['errors'][$errorType]++;
	}

	/**
	 * Sanitize row data for logging (remove sensitive information).
	 *
	 * @param array $row Row data
	 * @return array Sanitized row data
	 */
	private function sanitizeRowDataForLog(array $row): array {
		$sanitized = [];
		$sensitiveFields = ['password', 'token', 'secret', 'api_key', 'credit_card'];
		
		foreach ($row as $key => $value) {
			$keyLower = strtolower($key);
			$isSensitive = false;
			
			foreach ($sensitiveFields as $sensitiveField) {
				if (strpos($keyLower, $sensitiveField) !== false) {
					$isSensitive = true;
					break;
				}
			}
			
			if ($isSensitive) {
				$sanitized[$key] = '[REDACTED]';
			} else {
				// Truncate long values
				if (is_string($value) && strlen($value) > 100) {
					$sanitized[$key] = substr($value, 0, 100) . '...';
				} else {
					$sanitized[$key] = $value;
				}
			}
		}
		
		return $sanitized;
	}

	/**
	 * Get descriptive error message for formula errors.
	 *
	 * @param string $errorType Error type
	 * @param string $formula Formula expression
	 * @return string User-friendly error message
	 */
	public function getErrorMessage(string $errorType, string $formula = ''): string {
		$messages = [
			'validation' => 'Formula syntax is invalid. Please check your formula expression.',
			'division_by_zero' => 'Formula resulted in division by zero.',
			'arithmetic_error' => 'Formula contains an arithmetic error.',
			'evaluation_error' => 'Formula could not be evaluated.',
			'unknown' => 'An unknown error occurred while evaluating the formula.',
		];
		
		return $messages[$errorType] ?? $messages['unknown'];
	}

	/**
	 * Parse and evaluate a formula expression against a row of data.
	 *
	 * Short-circuits for trivial cases (plain numeric literals and single-variable
	 * formulas) to avoid parser overhead. Supports mathematical operations, string
	 * operations, and conditional logic.
	 *
	 * @param string $formula  Formula expression (before header substitution)
	 * @param array  $headers  Map of original header → sanitized variable name
	 * @param array  $row      Row data keyed by original header names
	 * @return float|int|string Evaluated result
	 */
	private function parsing($formula, $headers, $row) {
		$vars    = [];
		
		// Check if formula contains string operations or conditional logic
		$hasStringOps = $this->hasStringOperations($formula);
		$hasConditional = $this->hasConditionalLogic($formula);
		
		if ($hasStringOps) {
			return $this->evaluateStringFormula($formula, $headers, $row);
		}
		
		if ($hasConditional) {
			return $this->evaluateConditionalFormula($formula, $headers, $row);
		}
		
		// Mathematical operations (existing logic)
		// First, expand mathematical functions BEFORE header substitution
		// so we can properly resolve field names
		$formula = $this->expandMathematicalFunctionsEarly($formula, $headers, $row);
		
		// If the formula is now just a number (all functions evaluated), return it
		if (is_numeric(trim($formula))) {
			return (float)trim($formula);
		}
		
		$formula = str_replace(array('$', '_', '&'), '', strtr($formula, $headers));

		foreach ($headers as $origHeader => $sanitizedHeader) {
			$vars[$sanitizedHeader] = (float)$row[$origHeader];
		}

		// Short-circuit 1: plain numeric literal — no need to invoke the parser.
		if (is_numeric(trim($formula))) {
			return (float)trim($formula);
		}

		// Short-circuit 2: formula resolves to a single variable reference.
		$trimmed = trim($formula);
		if (isset($vars[$trimmed])) {
			return $vars[$trimmed];
		}

		return $this->solve($formula, $vars);
	}

	/**
	 * Expand mathematical functions early (before header substitution).
	 *
	 * This version works with original field names from the row data.
	 *
	 * @param string $formula Formula expression
	 * @param array $headers Headers map
	 * @param array $row Row data
	 * @return string Formula with evaluated functions
	 */
	private function expandMathematicalFunctionsEarly(string $formula, array $headers, array $row): string {
		// Build a map of field values using original names
		$fieldValues = [];
		foreach ($headers as $origHeader => $sanitizedHeader) {
			$fieldValues[$origHeader] = (float)($row[$origHeader] ?? 0);
		}
		
		// Replace mathematical functions with their evaluated results
		$mathFunctions = [
			'abs', 'ceil', 'floor', 'round', 'sqrt', 'exp', 'log', 'log10',
			'sin', 'cos', 'tan', 'asin', 'acos', 'atan',
		];

		// Process functions multiple times to handle nested calls
		$maxIterations = 10;
		for ($i = 0; $i < $maxIterations; $i++) {
			$originalFormula = $formula;
			
			foreach ($mathFunctions as $func) {
				// Match function calls like abs(x) or sqrt(25)
				// Use a more specific pattern that matches the function name followed by parentheses
				$pattern = '/\b' . preg_quote($func, '/') . '\s*\(([^()]+)\)/i';
				$formula = preg_replace_callback($pattern, function($matches) use ($func, $fieldValues) {
					$arg = trim($matches[1]);
					
					// If argument is a field name, get its value
					if (isset($fieldValues[$arg])) {
						$value = $fieldValues[$arg];
					} elseif (is_numeric($arg)) {
						$value = (float)$arg;
					} else {
						// Try to evaluate as a simple expression
						// Replace field names with values
						$expr = $arg;
						foreach ($fieldValues as $field => $val) {
							$expr = str_replace($field, (string)$val, $expr);
						}
						// Evaluate if it's now numeric
						if (is_numeric($expr)) {
							$value = (float)$expr;
						} else {
							// Can't evaluate, return 0
							return '0';
						}
					}
					
					// Apply the mathematical function
					try {
						$result = call_user_func($func, $value);
						return (string)$result;
					} catch (\Exception $e) {
						return '0';
					}
				}, $formula);
			}
			
			// If formula didn't change, we're done
			if ($formula === $originalFormula) {
				break;
			}
		}

		// Handle pow(base, exp) separately as it takes two arguments
		$formula = preg_replace_callback('/\bpow\s*\(([^,()]+),([^)()]+)\)/i', function($matches) use ($fieldValues) {
			$base = trim($matches[1]);
			$exp = trim($matches[2]);
			
			$baseValue = isset($fieldValues[$base]) ? $fieldValues[$base] : (is_numeric($base) ? (float)$base : 0);
			$expValue = isset($fieldValues[$exp]) ? $fieldValues[$exp] : (is_numeric($exp) ? (float)$exp : 0);
			
			try {
				return (string)pow($baseValue, $expValue);
			} catch (\Exception $e) {
				return '0';
			}
		}, $formula);

		// Handle min(a, b, ...) and max(a, b, ...)
		foreach (['min', 'max'] as $func) {
			$pattern = '/\b' . preg_quote($func, '/') . '\s*\(([^()]+)\)/i';
			$formula = preg_replace_callback($pattern, function($matches) use ($func, $fieldValues) {
				$args = array_map('trim', explode(',', $matches[1]));
				$values = [];
				
				foreach ($args as $arg) {
					if (isset($fieldValues[$arg])) {
						$values[] = $fieldValues[$arg];
					} elseif (is_numeric($arg)) {
						$values[] = (float)$arg;
					} else {
						// Try to evaluate as expression
						$expr = $arg;
						foreach ($fieldValues as $field => $val) {
							$expr = str_replace($field, (string)$val, $expr);
						}
						if (is_numeric($expr)) {
							$values[] = (float)$expr;
						}
					}
				}
				
				if (empty($values)) {
					return '0';
				}
				
				return (string)call_user_func($func, ...$values);
			}, $formula);
		}

		return $formula;
	}

	/**
	 * Expand mathematical functions in formula.
	 *
	 * Supports: abs, ceil, floor, round, sqrt, pow, exp, log, log10,
	 * sin, cos, tan, asin, acos, atan, min, max
	 *
	 * @param string $formula Formula expression
	 * @param array $vars Variables map
	 * @return string Expanded formula with evaluated functions
	 */
	private function expandMathematicalFunctions(string $formula, array $vars): string {
		// Replace mathematical functions with their evaluated results
		$mathFunctions = [
			'abs', 'ceil', 'floor', 'round', 'sqrt', 'exp', 'log', 'log10',
			'sin', 'cos', 'tan', 'asin', 'acos', 'atan',
		];

		foreach ($mathFunctions as $func) {
			// Match function calls like abs(x) or sqrt(25)
			$pattern = '/\b' . $func . '\s*\(([^)]+)\)/i';
			$formula = preg_replace_callback($pattern, function($matches) use ($func, $vars) {
				$arg = trim($matches[1]);
				
				// If argument is a variable, get its value
				if (isset($vars[$arg])) {
					$value = $vars[$arg];
				} elseif (is_numeric($arg)) {
					$value = (float)$arg;
				} else {
					// Try to evaluate the argument as an expression using the parser
					try {
						$value = $this->solve($arg, $vars);
					} catch (\Exception $e) {
						return '0';
					}
				}
				
				// Apply the mathematical function
				try {
					$result = call_user_func($func, $value);
					return (string)$result;
				} catch (\Exception $e) {
					return '0';
				}
			}, $formula);
		}

		// Handle pow(base, exp) separately as it takes two arguments
		$formula = preg_replace_callback('/\bpow\s*\(([^,]+),([^)]+)\)/i', function($matches) use ($vars) {
			$base = trim($matches[1]);
			$exp = trim($matches[2]);
			
			$baseValue = isset($vars[$base]) ? $vars[$base] : (is_numeric($base) ? (float)$base : 0);
			$expValue = isset($vars[$exp]) ? $vars[$exp] : (is_numeric($exp) ? (float)$exp : 0);
			
			try {
				return (string)pow($baseValue, $expValue);
			} catch (\Exception $e) {
				return '0';
			}
		}, $formula);

		// Handle min(a, b, ...) and max(a, b, ...)
		foreach (['min', 'max'] as $func) {
			$pattern = '/\b' . $func . '\s*\(([^)]+)\)/i';
			$formula = preg_replace_callback($pattern, function($matches) use ($func, $vars) {
				$args = array_map('trim', explode(',', $matches[1]));
				$values = [];
				
				foreach ($args as $arg) {
					if (isset($vars[$arg])) {
						$values[] = $vars[$arg];
					} elseif (is_numeric($arg)) {
						$values[] = (float)$arg;
					} else {
						// Try to evaluate as expression
						try {
							$values[] = $this->solve($arg, $vars);
						} catch (\Exception $e) {
							// Skip invalid values
						}
					}
				}
				
				if (empty($values)) {
					return '0';
				}
				
				return (string)call_user_func($func, ...$values);
			}, $formula);
		}

		return $formula;
	}

	/**
	 * Check if formula contains string operations.
	 *
	 * @param string $formula Formula expression
	 * @return bool True if contains string operations
	 */
	private function hasStringOperations(string $formula): bool {
		$stringFunctions = ['concat', 'substring', 'uppercase', 'lowercase', 'length', 'trim'];
		foreach ($stringFunctions as $func) {
			if (stripos($formula, $func) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Evaluate formula with string operations.
	 *
	 * Supports: concat, substring, uppercase, lowercase, length, trim
	 *
	 * @param string $formula Formula expression
	 * @param array $headers Headers map
	 * @param array $row Row data
	 * @return string Evaluated result
	 */
	private function evaluateStringFormula(string $formula, array $headers, array $row): string {
		// Replace field references with actual values
		$vars = [];
		foreach ($headers as $origHeader => $sanitizedHeader) {
			$vars[$sanitizedHeader] = $row[$origHeader] ?? '';
			// Also support direct field references
			$formula = str_replace($origHeader, "'{$row[$origHeader]}'", $formula);
		}

		// Process string functions
		// concat(a, b, c) - concatenate strings
		$formula = preg_replace_callback('/\bconcat\s*\(([^)]+)\)/i', function($matches) use ($vars, $row, $headers) {
			$args = $this->parseArguments($matches[1], $vars, $row, $headers);
			return "'" . implode('', $args) . "'";
		}, $formula);

		// substring(str, start, length) - extract substring
		$formula = preg_replace_callback('/\bsubstring\s*\(([^)]+)\)/i', function($matches) use ($vars, $row, $headers) {
			$args = $this->parseArguments($matches[1], $vars, $row, $headers);
			$str = $args[0] ?? '';
			$start = isset($args[1]) ? (int)$args[1] : 0;
			$length = isset($args[2]) ? (int)$args[2] : null;
			
			if ($length !== null) {
				return "'" . substr($str, $start, $length) . "'";
			}
			return "'" . substr($str, $start) . "'";
		}, $formula);

		// uppercase(str) - convert to uppercase
		$formula = preg_replace_callback('/\buppercase\s*\(([^)]+)\)/i', function($matches) use ($vars, $row, $headers) {
			$args = $this->parseArguments($matches[1], $vars, $row, $headers);
			return "'" . strtoupper($args[0] ?? '') . "'";
		}, $formula);

		// lowercase(str) - convert to lowercase
		$formula = preg_replace_callback('/\blowercase\s*\(([^)]+)\)/i', function($matches) use ($vars, $row, $headers) {
			$args = $this->parseArguments($matches[1], $vars, $row, $headers);
			return "'" . strtolower($args[0] ?? '') . "'";
		}, $formula);

		// length(str) - get string length
		$formula = preg_replace_callback('/\blength\s*\(([^)]+)\)/i', function($matches) use ($vars, $row, $headers) {
			$args = $this->parseArguments($matches[1], $vars, $row, $headers);
			return strlen($args[0] ?? '');
		}, $formula);

		// trim(str) - trim whitespace
		$formula = preg_replace_callback('/\btrim\s*\(([^)]+)\)/i', function($matches) use ($vars, $row, $headers) {
			$args = $this->parseArguments($matches[1], $vars, $row, $headers);
			return "'" . trim($args[0] ?? '') . "'";
		}, $formula);

		// Remove quotes from final result
		$result = trim($formula, "'\"");
		
		// Apply XSS protection to string results
		return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Parse function arguments from a comma-separated string.
	 *
	 * @param string $argsString Arguments string
	 * @param array $vars Variables map
	 * @param array $row Row data
	 * @param array $headers Headers map
	 * @return array Parsed arguments
	 */
	private function parseArguments(string $argsString, array $vars, array $row, array $headers): array {
		$args = array_map('trim', explode(',', $argsString));
		$result = [];
		
		foreach ($args as $arg) {
			// Remove quotes if present
			$arg = trim($arg, "'\"");
			
			// Check if it's a field reference
			if (isset($row[$arg])) {
				$result[] = $row[$arg];
			} elseif (isset($vars[$arg])) {
				$result[] = $vars[$arg];
			} elseif (is_numeric($arg)) {
				$result[] = $arg;
			} else {
				// Try to find field by sanitized name
				$found = false;
				foreach ($headers as $origHeader => $sanitizedHeader) {
					if ($sanitizedHeader === $arg || $origHeader === $arg) {
						$result[] = $row[$origHeader] ?? '';
						$found = true;
						break;
					}
				}
				if (!$found) {
					$result[] = $arg;
				}
			}
		}
		
		return $result;
	}

	/**
	 * Check if formula contains conditional logic.
	 *
	 * @param string $formula Formula expression
	 * @return bool True if contains conditional logic
	 */
	private function hasConditionalLogic(string $formula): bool {
		$conditionalFunctions = ['if', 'and', 'or', 'not'];
		foreach ($conditionalFunctions as $func) {
			if (stripos($formula, $func) !== false) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Evaluate formula with conditional logic.
	 *
	 * Supports: if(condition, true_value, false_value), and(a, b), or(a, b), not(a)
	 *
	 * @param string $formula Formula expression
	 * @param array $headers Headers map
	 * @param array $row Row data
	 * @return mixed Evaluated result
	 */
	private function evaluateConditionalFormula(string $formula, array $headers, array $row) {
		// Replace field references with actual values
		$vars = [];
		foreach ($headers as $origHeader => $sanitizedHeader) {
			$value = $row[$origHeader] ?? 0;
			$vars[$sanitizedHeader] = $value;
			$vars[$origHeader] = $value;
		}

		// Process conditional functions from innermost to outermost
		$maxIterations = 100; // Prevent infinite loops
		$iteration = 0;
		
		while ($iteration < $maxIterations && $this->hasConditionalLogic($formula)) {
			$iteration++;
			$originalFormula = $formula;
			
			// Process NOT first (highest precedence)
			$formula = preg_replace_callback('/\bnot\s*\(([^()]+)\)/i', function($matches) use ($vars, $row, $headers) {
				$arg = $this->evaluateExpression($matches[1], $vars, $row, $headers);
				return $this->isTruthy($arg) ? '0' : '1';
			}, $formula);

			// Process AND
			$formula = preg_replace_callback('/\band\s*\(([^()]+)\)/i', function($matches) use ($vars, $row, $headers) {
				$args = $this->parseArguments($matches[1], $vars, $row, $headers);
				foreach ($args as $arg) {
					if (!$this->isTruthy($arg)) {
						return '0';
					}
				}
				return '1';
			}, $formula);

			// Process OR
			$formula = preg_replace_callback('/\bor\s*\(([^()]+)\)/i', function($matches) use ($vars, $row, $headers) {
				$args = $this->parseArguments($matches[1], $vars, $row, $headers);
				foreach ($args as $arg) {
					if ($this->isTruthy($arg)) {
						return '1';
					}
				}
				return '0';
			}, $formula);

			// Process IF
			$formula = preg_replace_callback('/\bif\s*\(([^,]+),([^,]+),([^)]+)\)/i', function($matches) use ($vars, $row, $headers) {
				$condition = $this->evaluateExpression(trim($matches[1]), $vars, $row, $headers);
				$trueValue = $this->evaluateExpression(trim($matches[2]), $vars, $row, $headers);
				$falseValue = $this->evaluateExpression(trim($matches[3]), $vars, $row, $headers);
				
				return $this->isTruthy($condition) ? $trueValue : $falseValue;
			}, $formula);
			
			// If formula didn't change, break to avoid infinite loop
			if ($formula === $originalFormula) {
				break;
			}
		}

		// Final evaluation - check if result is numeric or string
		$result = trim($formula, "'\"");
		
		if (is_numeric($result)) {
			return (float)$result;
		}
		
		// Apply XSS protection to string results
		return htmlspecialchars($result, ENT_QUOTES, 'UTF-8');
	}

	/**
	 * Evaluate a simple expression (comparison, arithmetic, or value).
	 *
	 * @param string $expression Expression to evaluate
	 * @param array $vars Variables map
	 * @param array $row Row data
	 * @param array $headers Headers map
	 * @return mixed Evaluated result
	 */
	private function evaluateExpression(string $expression, array $vars, array $row, array $headers) {
		$expression = trim($expression);
		
		// Handle comparison operators
		$comparisonOps = [
			'>=' => function($a, $b) { return $a >= $b; },
			'<=' => function($a, $b) { return $a <= $b; },
			'==' => function($a, $b) { return $a == $b; },
			'!=' => function($a, $b) { return $a != $b; },
			'>' => function($a, $b) { return $a > $b; },
			'<' => function($a, $b) { return $a < $b; },
		];
		
		foreach ($comparisonOps as $op => $callback) {
			if (strpos($expression, $op) !== false) {
				$parts = explode($op, $expression, 2);
				if (count($parts) === 2) {
					$left = $this->resolveValue(trim($parts[0]), $vars, $row, $headers);
					$right = $this->resolveValue(trim($parts[1]), $vars, $row, $headers);
					return $callback($left, $right) ? 1 : 0;
				}
			}
		}
		
		// Handle arithmetic operators
		$arithmeticOps = ['+', '-', '*', '/', '%'];
		foreach ($arithmeticOps as $op) {
			if (strpos($expression, $op) !== false) {
				// Use the EOS parser for arithmetic
				try {
					$resolvedExpression = $expression;
					foreach ($vars as $varName => $varValue) {
						$resolvedExpression = str_replace($varName, $varValue, $resolvedExpression);
					}
					return $this->solve($resolvedExpression, []);
				} catch (\Exception $e) {
					return 0;
				}
			}
		}
		
		// Resolve as a simple value
		return $this->resolveValue($expression, $vars, $row, $headers);
	}

	/**
	 * Resolve a value from expression (variable, field, or literal).
	 *
	 * @param string $value Value to resolve
	 * @param array $vars Variables map
	 * @param array $row Row data
	 * @param array $headers Headers map
	 * @return mixed Resolved value
	 */
	private function resolveValue(string $value, array $vars, array $row, array $headers) {
		$value = trim($value, "'\" ");
		
		// Check if it's a numeric literal
		if (is_numeric($value)) {
			return (float)$value;
		}
		
		// Check if it's a variable
		if (isset($vars[$value])) {
			return $vars[$value];
		}
		
		// Check if it's a field reference
		if (isset($row[$value])) {
			return $row[$value];
		}
		
		// Check sanitized headers
		foreach ($headers as $origHeader => $sanitizedHeader) {
			if ($sanitizedHeader === $value) {
				return $row[$origHeader] ?? 0;
			}
		}
		
		// Return as string literal
		return $value;
	}

	/**
	 * Check if a value is truthy.
	 *
	 * @param mixed $value Value to check
	 * @return bool True if truthy
	 */
	private function isTruthy($value): bool {
		if (is_bool($value)) {
			return $value;
		}
		if (is_numeric($value)) {
			return (float)$value != 0;
		}
		if (is_string($value)) {
			$lower = strtolower(trim($value));
			return $lower !== '' && $lower !== 'false' && $lower !== '0';
		}
		return !empty($value);
	}

	/**
	 * Sanitize column header names into safe variable names for the EOS parser.
	 *
	 * Replaces digit characters with letters so the resulting names are valid
	 * identifiers (EOS parser does not accept names starting with digits).
	 *
	 * @param array $headers_formula List of original header/field names
	 * @return void
	 */
	private function header_sanitizer($headers_formula) {
		foreach ($headers_formula as $key => $header) {
			$this->headers[$header] = str_replace(range('0', '9'), range('a', 'j'), "canvastacktacolumn{$key}");
		}
	}

	/**
	 * Validate formula syntax and security.
	 *
	 * Checks for:
	 * - Maximum formula length
	 * - Balanced parentheses
	 * - Maximum nesting depth
	 * - Allowed operators only
	 * - Allowed functions only
	 * - No dangerous patterns (eval, exec, system, etc.)
	 *
	 * @param string $formula Formula expression to validate
	 * @return bool True if valid
	 * @throws \InvalidArgumentException If formula is invalid
	 *
	 * @security Validates formula syntax to prevent code injection
	 */
	private function validateFormulaSyntax(string $formula): bool {
		// Check formula length
		if (strlen($formula) > self::MAX_FORMULA_LENGTH) {
			throw new FormulaException(
				sprintf('Formula exceeds maximum length of %d characters', self::MAX_FORMULA_LENGTH)
			);
		}

		// Check for dangerous patterns
		$dangerousPatterns = [
			'/\beval\b/i',
			'/\bexec\b/i',
			'/\bsystem\b/i',
			'/\bshell_exec\b/i',
			'/\bpassthru\b/i',
			'/\bproc_open\b/i',
			'/\bpopen\b/i',
			'/\b__\w+__\b/', // Magic methods/constants
			'/\$\$/', // Variable variables
			'/`/', // Backticks
		];

		foreach ($dangerousPatterns as $pattern) {
			if (preg_match($pattern, $formula)) {
				throw new FormulaException('Formula contains dangerous patterns');
			}
		}

		// Check balanced parentheses and nesting depth
		$this->validateParentheses($formula);

		// Check for allowed operators and functions
		$this->validateOperatorsAndFunctions($formula);

		return true;
	}

	/**
	 * Validate parentheses are balanced and within nesting depth limit.
	 *
	 * @param string $formula Formula expression
	 * @return void
	 * @throws FormulaException If parentheses are unbalanced or too deeply nested
	 */
	private function validateParentheses(string $formula): void {
		$depth = 0;
		$maxDepth = 0;
		$length = strlen($formula);

		for ($i = 0; $i < $length; $i++) {
			if ($formula[$i] === '(') {
				$depth++;
				$maxDepth = max($maxDepth, $depth);
			} elseif ($formula[$i] === ')') {
				$depth--;
				if ($depth < 0) {
					throw new FormulaException('Formula has unbalanced parentheses');
				}
			}
		}

		if ($depth !== 0) {
			throw new FormulaException('Formula has unbalanced parentheses');
		}

		if ($maxDepth > self::MAX_NESTING_DEPTH) {
			throw new FormulaException(
				sprintf('Formula nesting depth exceeds maximum of %d', self::MAX_NESTING_DEPTH)
			);
		}
	}

	/**
	 * Validate that formula only uses allowed operators and functions.
	 *
	 * @param string $formula Formula expression
	 * @return void
	 * @throws FormulaException If formula contains disallowed operators or functions
	 */
	private function validateOperatorsAndFunctions(string $formula): void {
		// Extract function names from formula
		preg_match_all('/\b([a-z_][a-z0-9_]*)\s*\(/i', $formula, $matches);
		$functions = $matches[1] ?? [];

		foreach ($functions as $function) {
			$functionLower = strtolower($function);
			if (!in_array($functionLower, self::ALLOWED_FUNCTIONS, true)) {
				throw new FormulaException(
					sprintf('Function "%s" is not allowed in formulas', $function)
				);
			}
		}

		// Check for disallowed characters (after removing allowed operators, functions, numbers, and field references)
		$cleaned = $formula;
		
		// Remove allowed operators
		foreach (self::ALLOWED_OPERATORS as $op) {
			$cleaned = str_replace($op, '', $cleaned);
		}
		
		// Remove commas (used in function arguments)
		$cleaned = str_replace(',', '', $cleaned);
		
		// Remove quotes (used in string literals)
		$cleaned = str_replace(['"', "'"], '', $cleaned);
		
		// Remove numbers (including decimals)
		$cleaned = preg_replace('/\d+\.?\d*/', '', $cleaned);
		
		// Remove allowed function names
		foreach (self::ALLOWED_FUNCTIONS as $func) {
			$cleaned = str_ireplace($func, '', $cleaned);
		}
		
		// Remove field references (alphanumeric and underscores)
		$cleaned = preg_replace('/[a-z_][a-z0-9_]*/i', '', $cleaned);
		
		// Remove whitespace
		$cleaned = preg_replace('/\s+/', '', $cleaned);
		
		// Remove dots (used in decimals and field access)
		$cleaned = str_replace('.', '', $cleaned);
		
		// If anything remains, it's an invalid character
		if (!empty($cleaned)) {
			throw new FormulaException(
				'Formula contains invalid characters or operators'
			);
		}
	}

	/**
	 * Build a deterministic cache key for a given formula + row combination.
	 *
	 * @param string $formulaLogic  The raw formula logic string
	 * @param array  $fieldLists    List of field names used by the formula
	 * @param array  $row           Full row data
	 * @return string MD5 hash suitable as an array key
	 */
	private function buildCacheKey(string $formulaLogic, array $fieldLists, array $row): string {
		$relevant = [];
		foreach ($fieldLists as $field) {
			$relevant[$field] = $row[$field] ?? null;
		}
		return md5($formulaLogic . serialize($relevant));
	}

	/**
	 * Retrieve aggregate performance metrics for all Formula instances.
	 *
	 * @return array{call_count: int, cache_hits: int, cache_misses: int, total_time_ms: float}
	 */
	public static function getPerformanceMetrics(): array {
		return self::$metrics;
	}

	/**
	 * Reset all aggregate performance metrics to zero.
	 *
	 * @return void
	 */
	public static function resetPerformanceMetrics(): void {
		self::$metrics = [
			'call_count'    => 0,
			'cache_hits'    => 0,
			'cache_misses'  => 0,
			'total_time_ms' => 0.0,
		];
	}
}
