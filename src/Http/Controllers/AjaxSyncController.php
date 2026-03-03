<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Http\Controllers;

use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AjaxSyncController - Handles Ajax sync requests for cascading dropdowns.
 *
 * This controller processes Ajax requests from cascading dropdown fields,
 * decrypts and validates queries, executes them securely with parameterized
 * queries, and returns JSON responses with options.
 *
 * Security Features:
 * - Query encryption/decryption
 * - SQL injection prevention (SELECT-only queries)
 * - Parameterized query execution
 * - Suspicious query logging
 * - Response caching (5 minutes)
 */
class AjaxSyncController extends Controller
{
    protected QueryEncryption $encryption;

    /**
     * Cache duration in seconds (5 minutes).
     */
    protected const CACHE_DURATION = 300;

    /**
     * Maximum number of options to return.
     */
    protected const MAX_OPTIONS = 1000;

    public function __construct(QueryEncryption $encryption)
    {
        $this->encryption = $encryption;
    }

    /**
     * Handle Ajax sync request.
     *
     * Processes the Ajax request by:
     * 1. Validating the request data
     * 2. Decrypting the query and parameters
     * 3. Validating the query is SELECT-only
     * 4. Checking cache for existing results
     * 5. Executing the query with parameterized binding
     * 6. Formatting and returning the response
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        try {
            // Validate request data using Validator facade
            $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
                'relationship' => 'required|array',
                'relationship.source' => 'required|string',
                'relationship.target' => 'required|string',
                'relationship.values' => 'required|string',
                'relationship.labels' => 'required|string',
                'relationship.query' => 'required|string',
                'sourceValue' => 'required',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation failed', $validator->errors()->toArray(), 422);
            }

            $validated = $validator->validated();
            $relationship = $validated['relationship'];
            $sourceValue = $validated['sourceValue'];

            // Decrypt parameters
            $values = $this->decryptParameter($relationship['values'], 'values');
            $labels = $this->decryptParameter($relationship['labels'], 'labels');
            $query = $this->decryptParameter($relationship['query'], 'query');

            // Validate query is SELECT only
            if (!$this->isSelectQuery($query)) {
                Log::warning('Ajax sync: Non-SELECT query attempted', [
                    'query' => $query,
                    'source' => $relationship['source'],
                    'ip' => $request->ip(),
                ]);

                return $this->error('Invalid query type. Only SELECT queries are allowed.', null, 403);
            }

            // Check for SQL injection patterns
            if ($this->hasSuspiciousPatterns($query)) {
                Log::warning('Ajax sync: Suspicious query pattern detected', [
                    'query' => $query,
                    'source' => $relationship['source'],
                    'ip' => $request->ip(),
                ]);

                return $this->error('Query contains suspicious patterns.', null, 403);
            }

            // Generate cache key
            $cacheKey = $this->generateCacheKey($query, $sourceValue);

            // Check if data is already cached
            $isCached = Cache::has($cacheKey);

            // Try to get from cache
            $options = Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($query, $sourceValue, $values, $labels) {
                return $this->executeQuery($query, $sourceValue, $values, $labels);
            });

            return $this->success([
                'options' => $options,
                'cached' => $isCached,
            ]);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            Log::error('Ajax sync: Decryption failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return $this->error('Invalid encrypted data', null, 400);
        } catch (\Exception $e) {
            Log::error('Ajax sync: Query execution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to fetch options', null, 500);
        }
    }

    /**
     * Decrypt a parameter value.
     *
     * @param string $encrypted Encrypted value
     * @param string $paramName Parameter name for error messages
     * @return mixed Decrypted value
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    protected function decryptParameter(string $encrypted, string $paramName): mixed
    {
        try {
            return $this->encryption->decrypt($encrypted);
        } catch (\Exception $e) {
            throw new \Illuminate\Contracts\Encryption\DecryptException(
                "Failed to decrypt parameter: {$paramName}"
            );
        }
    }

    /**
     * Validate that the query is a SELECT statement only.
     *
     * @param string $query SQL query
     * @return bool True if query is SELECT-only
     */
    protected function isSelectQuery(string $query): bool
    {
        $query = trim(strtoupper($query));

        // Must start with SELECT
        if (!str_starts_with($query, 'SELECT')) {
            return false;
        }

        // Must not contain dangerous keywords
        $dangerousKeywords = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER',
            'TRUNCATE', 'REPLACE', 'EXEC', 'EXECUTE', 'CALL',
            'GRANT', 'REVOKE', 'LOAD', 'OUTFILE', 'DUMPFILE',
        ];

        foreach ($dangerousKeywords as $keyword) {
            if (str_contains($query, $keyword)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check for suspicious SQL injection patterns.
     *
     * @param string $query SQL query
     * @return bool True if suspicious patterns found
     */
    protected function hasSuspiciousPatterns(string $query): bool
    {
        $suspiciousPatterns = [
            '/;\s*SELECT/i',           // Multiple statements
            '/;\s*DROP/i',             // Drop statements
            '/;\s*DELETE/i',           // Delete statements
            '/;\s*UPDATE/i',           // Update statements
            '/;\s*INSERT/i',           // Insert statements
            '/UNION\s+SELECT/i',       // Union injection
            '/--/',                    // SQL comments
            '/#/',                     // MySQL comments
            '/\/\*/',                  // Multi-line comments
            '/\bOR\b.*=.*\bOR\b/i',   // OR injection
            '/\bAND\b.*=.*\bAND\b/i', // AND injection
            '/0x[0-9a-f]+/i',         // Hex values (potential injection)
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Execute the query with parameterized binding.
     *
     * @param string $query SQL query with ? placeholder
     * @param mixed $sourceValue Value to bind to placeholder
     * @param string $values Column name for option values
     * @param string $labels Column name for option labels
     * @return array Array of options with value and label
     */
    protected function executeQuery(string $query, mixed $sourceValue, string $values, string $labels): array
    {
        // Execute query with parameterized binding
        $results = DB::select($query, [$sourceValue]);

        // Limit results to prevent memory issues
        if (count($results) > self::MAX_OPTIONS) {
            $results = array_slice($results, 0, self::MAX_OPTIONS);
        }

        // Format results as options array
        $options = [];
        foreach ($results as $row) {
            $row = (array) $row;

            $options[] = [
                'value' => $row[$values] ?? '',
                'label' => $row[$labels] ?? $row[$values] ?? '',
            ];
        }

        return $options;
    }

    /**
     * Generate cache key for query and source value.
     *
     * @param string $query SQL query
     * @param mixed $sourceValue Source field value
     * @return string Cache key
     */
    protected function generateCacheKey(string $query, mixed $sourceValue): string
    {
        return 'ajax_sync:' . md5($query . '|' . $sourceValue);
    }
}
