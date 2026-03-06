<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;

/**
 * TableUrlState - Manages table state in URL parameters.
 *
 * This class provides URL-based state management for tables:
 * - Encode table state to URL query parameters
 * - Decode URL query parameters to table state
 * - Support for sort, filters, page, page size, search
 * - Clean URL generation with minimal parameters
 *
 * @version 1.0.0
 */
class TableUrlState
{
    /**
     * URL parameter prefix for table state.
     */
    protected const PARAM_PREFIX = 'table_';

    /**
     * Table identifier (unique per table).
     *
     * @var string
     */
    protected string $tableId;

    /**
     * Current state data.
     *
     * @var array<string, mixed>
     */
    protected array $state = [];

    /**
     * Constructor.
     *
     * @param string $tableId Unique table identifier
     */
    public function __construct(string $tableId)
    {
        $this->tableId = $tableId;
    }

    /**
     * Convert state to URL query parameters.
     *
     * @param array<string, mixed> $state State data
     * @return array<string, string> URL query parameters
     */
    public function toUrl(array $state): array
    {
        $params = [];

        // Sort state
        if (isset($state['sort']['column'])) {
            $params[$this->getParamKey('sort')] = $state['sort']['column'];
            $params[$this->getParamKey('order')] = $state['sort']['direction'] ?? 'asc';
        }

        // Filter state
        if (!empty($state['filters'])) {
            foreach ($state['filters'] as $column => $value) {
                if ($value !== null && $value !== '') {
                    $params[$this->getParamKey('filter_' . $column)] = $this->encodeValue($value);
                }
            }
        }

        // Page number
        if (isset($state['current_page']) && $state['current_page'] > 1) {
            $params[$this->getParamKey('page')] = (string) $state['current_page'];
        }

        // Page size
        if (isset($state['page_size'])) {
            $params[$this->getParamKey('per_page')] = (string) $state['page_size'];
        }

        // Search value
        if (isset($state['search']) && $state['search'] !== '') {
            $params[$this->getParamKey('search')] = $state['search'];
        }

        // Hidden columns
        if (!empty($state['hidden_columns'])) {
            $params[$this->getParamKey('hidden')] = implode(',', $state['hidden_columns']);
        }

        // Active tab (Requirement 32.4)
        if (isset($state['active_tab']) && $state['active_tab'] !== '') {
            $params[$this->getParamKey('tab')] = $state['active_tab'];
        }

        return $params;
    }

    /**
     * Parse URL query parameters to state.
     *
     * @param Request|array<string, mixed> $request Request object or query parameters
     * @return array<string, mixed> State data
     */
    public function fromUrl($request): array
    {
        $params = $request instanceof Request ? $request->query() : $request;
        $state = [];

        // Sort state
        $sortColumn = $params[$this->getParamKey('sort')] ?? null;
        if ($sortColumn) {
            $state['sort'] = [
                'column' => $sortColumn,
                'direction' => $params[$this->getParamKey('order')] ?? 'asc',
            ];
        }

        // Filter state
        $filters = [];
        foreach ($params as $key => $value) {
            if (str_starts_with($key, $this->getParamKey('filter_'))) {
                $column = substr($key, strlen($this->getParamKey('filter_')));
                $filters[$column] = $this->decodeValue($value);
            }
        }
        if (!empty($filters)) {
            $state['filters'] = $filters;
        }

        // Page number
        $page = $params[$this->getParamKey('page')] ?? null;
        if ($page) {
            $state['current_page'] = (int) $page;
        }

        // Page size
        $perPage = $params[$this->getParamKey('per_page')] ?? null;
        if ($perPage) {
            $state['page_size'] = (int) $perPage;
        }

        // Search value
        $search = $params[$this->getParamKey('search')] ?? null;
        if ($search) {
            $state['search'] = $search;
        }

        // Hidden columns
        $hidden = $params[$this->getParamKey('hidden')] ?? null;
        if ($hidden) {
            $state['hidden_columns'] = explode(',', $hidden);
        }

        // Active tab (Requirement 32.4)
        $activeTab = $params[$this->getParamKey('tab')] ?? null;
        if ($activeTab) {
            $state['active_tab'] = $activeTab;
        }

        return $state;
    }

    /**
     * Get parameter key with table prefix.
     *
     * @param string $key Parameter key
     * @return string Prefixed parameter key
     */
    protected function getParamKey(string $key): string
    {
        return self::PARAM_PREFIX . $this->tableId . '_' . $key;
    }

    /**
     * Encode complex value for URL.
     *
     * @param mixed $value Value to encode
     * @return string Encoded value
     */
    protected function encodeValue($value): string
    {
        if (is_array($value)) {
            return base64_encode(json_encode($value, JSON_THROW_ON_ERROR));
        }

        return (string) $value;
    }

    /**
     * Decode complex value from URL.
     *
     * @param string $value Encoded value
     * @return mixed Decoded value
     */
    protected function decodeValue(string $value)
    {
        // Try to decode as base64 JSON
        if (preg_match('/^[A-Za-z0-9+\/=]+$/', $value)) {
            $decoded = base64_decode($value, true);
            if ($decoded !== false) {
                $json = json_decode($decoded, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $json;
                }
            }
        }

        return $value;
    }

    /**
     * Generate URL with state parameters.
     *
     * @param string $baseUrl Base URL
     * @param array<string, mixed> $state State data
     * @return string Complete URL with query parameters
     */
    public function generateUrl(string $baseUrl, array $state): string
    {
        $params = $this->toUrl($state);

        if (empty($params)) {
            return $baseUrl;
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . http_build_query($params);
    }

    /**
     * Parse current request URL to state.
     *
     * @param Request $request Request object
     * @return array<string, mixed> State data
     */
    public function parseRequest(Request $request): array
    {
        return $this->fromUrl($request);
    }

    /**
     * Get clean URL parameters (remove empty values).
     *
     * @param array<string, mixed> $state State data
     * @return array<string, string> Clean URL parameters
     */
    public function getCleanParams(array $state): array
    {
        $params = $this->toUrl($state);

        return array_filter($params, function ($value) {
            return $value !== null && $value !== '';
        });
    }

    /**
     * Merge URL state with existing state.
     *
     * @param array<string, mixed> $existingState Existing state
     * @param Request|array<string, mixed> $request Request or query parameters
     * @return array<string, mixed> Merged state
     */
    public function mergeWithUrl(array $existingState, $request): array
    {
        $urlState = $this->fromUrl($request);

        return array_merge($existingState, $urlState);
    }

    /**
     * Check if URL has table state parameters.
     *
     * @param Request|array<string, mixed> $request Request or query parameters
     * @return bool True if URL has table state
     */
    public function hasUrlState($request): bool
    {
        $params = $request instanceof Request ? $request->query() : $request;

        foreach (array_keys($params) as $key) {
            if (str_starts_with($key, self::PARAM_PREFIX . $this->tableId . '_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove table state parameters from URL.
     *
     * @param string $url URL with query parameters
     * @return string URL without table state parameters
     */
    public function removeFromUrl(string $url): string
    {
        $parts = parse_url($url);
        $query = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);

            // Remove table state parameters
            $query = array_filter($query, function ($key) {
                return !str_starts_with($key, self::PARAM_PREFIX . $this->tableId . '_');
            }, ARRAY_FILTER_USE_KEY);
        }

        // Rebuild URL
        $result = $parts['scheme'] . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $result .= ':' . $parts['port'];
        }

        if (isset($parts['path'])) {
            $result .= $parts['path'];
        }

        if (!empty($query)) {
            $result .= '?' . http_build_query($query);
        }

        if (isset($parts['fragment'])) {
            $result .= '#' . $parts['fragment'];
        }

        return $result;
    }

    /**
     * Get table identifier.
     *
     * @return string
     */
    public function getTableId(): string
    {
        return $this->tableId;
    }

    /**
     * Get parameter prefix for this table.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return self::PARAM_PREFIX . $this->tableId . '_';
    }
}
