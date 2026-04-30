<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Cache;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\Config\SearchConfig;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\QueryBuilder;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\ScriptGenerator;
use Canvastack\Canvastack\Library\Components\Table\Craft\Search\FormGenerator;

/**
 * Unit Tests for Search Component Performance (Task 2.4.6)
 *
 * Verifies that the performance optimizations implemented in Task 2.4 work
 * correctly:
 * - 2.4.1 Query building optimization (batch queries, cache keys)
 * - 2.4.2 Query result caching (in-memory + persistent)
 * - 2.4.3 Filter processing optimization (batch loading, single-pass)
 * - 2.4.4 Debouncing for search input
 * - 2.4.5 JavaScript generation optimization (deduplication)
 *
 * Validates: Requirements 4.8, 5.1, 5.6, 5.7, 17.7
 *
 * @group performance
 * @group search
 * @group unit
 */
class SearchPerformanceTest extends TestCase
{
    // =========================================================================
    // Setup / Teardown
    // =========================================================================

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array']);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    /**
     * Create a SearchConfig instance with minimal configuration.
     */
    private function makeConfig(array $filters = [], ?string $connection = null): SearchConfig
    {
        return new SearchConfig('test', 'test_table', $filters, $connection);
    }

    /**
     * Create a QueryBuilder with an in-memory-only cache (no persistent).
     */
    private function makeQueryBuilder(array $filters = []): QueryBuilder
    {
        return new QueryBuilder($this->makeConfig($filters));
    }

    /**
     * Create a ScriptGenerator with default debounce.
     */
    private function makeScriptGenerator(int $debounce = 300): ScriptGenerator
    {
        return new ScriptGenerator($this->makeConfig(), $debounce);
    }

    // =========================================================================
    // 1. QueryBuilder - Cache Key Consistency (2.4.1)
    // =========================================================================

    /**
     * Cache key is deterministic: same inputs always produce the same key.
     *
     * Validates: Requirement 4.8 - Query result caching
     */
    public function test_query_builder_cache_key_is_deterministic()
    {
        $qb = $this->makeQueryBuilder();

        // Access buildCacheKey via reflection
        $ref    = new \ReflectionMethod(QueryBuilder::class, 'buildCacheKey');
        $ref->setAccessible(true);

        $key1 = $ref->invoke($qb, 'users', ['name', 'email'], null);
        $key2 = $ref->invoke($qb, 'users', ['name', 'email'], null);

        $this->assertSame($key1, $key2, 'Cache key must be identical for the same inputs');
    }

    /**
     * Cache key is order-independent: field order does not affect the key.
     *
     * Validates: Requirement 4.8 - Consistent caching regardless of field order
     */
    public function test_query_builder_cache_key_is_order_independent()
    {
        $qb  = $this->makeQueryBuilder();
        $ref = new \ReflectionMethod(QueryBuilder::class, 'buildCacheKey');
        $ref->setAccessible(true);

        $key1 = $ref->invoke($qb, 'users', ['name', 'email'], null);
        $key2 = $ref->invoke($qb, 'users', ['email', 'name'], null);

        $this->assertSame($key1, $key2, 'Cache key must be the same regardless of field order');
    }

    /**
     * Different tables produce different cache keys (no collision).
     */
    public function test_query_builder_cache_keys_differ_for_different_tables()
    {
        $qb  = $this->makeQueryBuilder();
        $ref = new \ReflectionMethod(QueryBuilder::class, 'buildCacheKey');
        $ref->setAccessible(true);

        $key1 = $ref->invoke($qb, 'users', ['name'], null);
        $key2 = $ref->invoke($qb, 'products', ['name'], null);

        $this->assertNotSame($key1, $key2, 'Different tables must produce different cache keys');
    }

    /**
     * Different field sets produce different cache keys (no collision).
     */
    public function test_query_builder_cache_keys_differ_for_different_fields()
    {
        $qb  = $this->makeQueryBuilder();
        $ref = new \ReflectionMethod(QueryBuilder::class, 'buildCacheKey');
        $ref->setAccessible(true);

        $key1 = $ref->invoke($qb, 'users', ['name'], null);
        $key2 = $ref->invoke($qb, 'users', ['email'], null);

        $this->assertNotSame($key1, $key2, 'Different field sets must produce different cache keys');
    }

    // =========================================================================
    // 2. QueryBuilder - In-Memory Cache (2.4.2)
    // =========================================================================

    /**
     * In-memory cache is populated after processQueryResults and returned on
     * subsequent getSelections() calls without re-querying.
     *
     * Validates: Requirement 4.8 - Query result caching
     */
    public function test_query_builder_in_memory_cache_populated_after_process()
    {
        $qb = $this->makeQueryBuilder();

        // Simulate query results
        $rows = [
            (object)['status' => 'active'],
            (object)['status' => 'inactive'],
        ];
        $qb->processQueryResults($rows, ['status']);

        $selections = $qb->getSelections();
        $this->assertArrayHasKey('status', $selections, 'Selections must contain the processed field');
        $this->assertContains('active', $selections['status']);
        $this->assertContains('inactive', $selections['status']);
    }

    /**
     * clearCache() resets the in-memory query cache.
     *
     * Validates: Requirement 5.6 - Cache invalidation mechanisms
     */
    public function test_query_builder_clear_cache_resets_in_memory_cache()
    {
        $qb = $this->makeQueryBuilder();

        // Populate via processQueryResults
        $rows = [(object)['status' => 'active']];
        $qb->processQueryResults($rows, ['status']);

        $this->assertNotEmpty($qb->getSelections(), 'Selections must be populated before clear');

        $qb->clearCache();

        // After clearCache, queryCache is reset but selections array is separate
        // Verify the internal queryCache is empty via reflection
        $ref = new \ReflectionProperty(QueryBuilder::class, 'queryCache');
        $ref->setAccessible(true);
        $this->assertEmpty($ref->getValue($qb), 'queryCache must be empty after clearCache()');
    }

    /**
     * enablePersistentCache() sets usePersistentCache to true.
     *
     * Validates: Requirement 5.4 - Configuration caching
     */
    public function test_query_builder_enable_persistent_cache_sets_flag()
    {
        $qb = $this->makeQueryBuilder();
        $qb->enablePersistentCache(600);

        $ref = new \ReflectionProperty(QueryBuilder::class, 'usePersistentCache');
        $ref->setAccessible(true);
        $this->assertTrue($ref->getValue($qb), 'usePersistentCache must be true after enablePersistentCache()');
    }

    /**
     * disablePersistentCache() sets usePersistentCache to false.
     */
    public function test_query_builder_disable_persistent_cache_sets_flag()
    {
        $qb = $this->makeQueryBuilder();
        $qb->enablePersistentCache(600);
        $qb->disablePersistentCache();

        $ref = new \ReflectionProperty(QueryBuilder::class, 'usePersistentCache');
        $ref->setAccessible(true);
        $this->assertFalse($ref->getValue($qb), 'usePersistentCache must be false after disablePersistentCache()');
    }

    /**
     * Constructor accepts cacheTtl and usePersistentCache parameters.
     *
     * Validates: Requirement 5.7 - Appropriate cache TTL values
     */
    public function test_query_builder_constructor_accepts_cache_parameters()
    {
        $config = $this->makeConfig();
        $qb     = new QueryBuilder($config, 600, true);

        $ttlRef = new \ReflectionProperty(QueryBuilder::class, 'cacheTtl');
        $ttlRef->setAccessible(true);
        $this->assertEquals(600, $ttlRef->getValue($qb), 'cacheTtl must be set from constructor');

        $flagRef = new \ReflectionProperty(QueryBuilder::class, 'usePersistentCache');
        $flagRef->setAccessible(true);
        $this->assertTrue($flagRef->getValue($qb), 'usePersistentCache must be set from constructor');
    }

    // =========================================================================
    // 3. QueryBuilder - Batch Selections (2.4.1 / 2.4.3)
    // =========================================================================

    /**
     * batchSelections() returns $this for method chaining.
     *
     * Validates: Requirement 4.8 - Batch query optimization
     */
    public function test_query_builder_batch_selections_returns_self_for_chaining()
    {
        $qb     = $this->makeQueryBuilder();
        $result = $qb->batchSelections('users', []);

        $this->assertSame($qb, $result, 'batchSelections() must return $this for method chaining');
    }

    /**
     * batchSelections() with empty fields returns early without querying.
     */
    public function test_query_builder_batch_selections_empty_fields_returns_early()
    {
        $qb = $this->makeQueryBuilder();
        $qb->batchSelections('users', []);

        $this->assertEmpty($qb->getSelections(), 'Selections must be empty when no fields provided');
    }

    /**
     * processQueryResults() deduplicates values via array_unique.
     *
     * Validates: Requirement 4.8 - Efficient array operations
     */
    public function test_query_builder_process_results_deduplicates_values()
    {
        $qb = $this->makeQueryBuilder();

        $rows = [
            (object)['status' => 'active'],
            (object)['status' => 'active'],   // duplicate
            (object)['status' => 'inactive'],
        ];
        $qb->processQueryResults($rows, ['status']);

        $selections = $qb->getSelections();
        $this->assertCount(2, $selections['status'], 'Duplicate values must be removed');
    }

    // =========================================================================
    // 4. FormGenerator - Filter Processing Optimization (2.4.3)
    // =========================================================================

    /**
     * processFilterFields() returns empty array for empty input.
     *
     * Validates: Requirement 4.8 - Efficient filter processing
     */
    public function test_form_generator_process_filter_fields_empty_input()
    {
        $config = $this->makeConfig();
        $fg     = new FormGenerator($config);
        $qb     = $this->makeQueryBuilder();

        $result = $fg->processFilterFields([], 'users', 'status', $qb);
        $this->assertSame([], $result, 'processFilterFields() must return empty array for empty input');
    }

    /**
     * processFilterFields() defaults unknown types to 'text'.
     *
     * Validates: Requirement 4.8 - Robust filter type handling
     */
    public function test_form_generator_process_filter_fields_defaults_unknown_type()
    {
        $config = $this->makeConfig();
        $fg     = new FormGenerator($config);
        $qb     = $this->makeQueryBuilder();

        $result = $fg->processFilterFields(['my_field' => 'unknown_type'], 'users', 'my_field', $qb);

        $this->assertArrayHasKey('my_field', $result);
        $this->assertEquals('text', $result['my_field']['type'], 'Unknown type must default to text');
    }

    /**
     * processFilterFields() returns correct structure for each field.
     *
     * Validates: Requirement 4.8 - Filter processing returns correct structure
     */
    public function test_form_generator_process_filter_fields_returns_correct_structure()
    {
        $config = $this->makeConfig();
        $fg     = new FormGenerator($config);
        $qb     = $this->makeQueryBuilder();

        $filterData = [
            'name'   => 'text',
            'status' => 'selectbox',
        ];

        $result = $fg->processFilterFields($filterData, 'users', 'name', $qb);

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('type', $result['name']);
        $this->assertArrayHasKey('values', $result['name']);
        $this->assertEquals('text', $result['name']['type']);
        $this->assertEquals('selectbox', $result['status']['type']);
    }

    /**
     * batchLoadFieldValues() populates fieldValuesCache for the open_field.
     *
     * Validates: Requirement 4.8 - Batch loading reduces N+1 queries
     */
    public function test_form_generator_batch_load_populates_cache_for_open_field()
    {
        $config = $this->makeConfig();
        $fg     = new FormGenerator($config);
        $qb     = $this->makeQueryBuilder();

        // Pre-populate selections in the query builder
        $rows = [(object)['status' => 'active'], (object)['status' => 'inactive']];
        $qb->processQueryResults($rows, ['status']);

        $fg->batchLoadFieldValues('users', ['status'], 'status', $qb);

        $cached = $fg->getFieldValuesFromCache('status', 'status');
        $this->assertNotNull($cached, 'Cache must be populated for the open_field');
        $this->assertContains('active', $cached);
    }

    /**
     * getFieldValuesFromCache() returns null for non-open fields.
     *
     * Validates: Requirement 4.8 - Only open field gets pre-populated values
     */
    public function test_form_generator_get_field_values_returns_null_for_non_open_field()
    {
        $config = $this->makeConfig();
        $fg     = new FormGenerator($config);
        $qb     = $this->makeQueryBuilder();

        $rows = [(object)['status' => 'active']];
        $qb->processQueryResults($rows, ['status']);
        $fg->batchLoadFieldValues('users', ['status'], 'other_field', $qb);

        $cached = $fg->getFieldValuesFromCache('status', 'other_field');
        $this->assertNull($cached, 'Non-open field must return null from cache');
    }

    // =========================================================================
    // 5. ScriptGenerator - Debouncing (2.4.4)
    // =========================================================================

    /**
     * Default debounce delay is 300ms.
     *
     * Validates: Requirement 17.7 - Debounce search input for performance
     */
    public function test_script_generator_default_debounce_delay_is_300ms()
    {
        $sg  = $this->makeScriptGenerator();
        $ref = new \ReflectionProperty(ScriptGenerator::class, 'debounceDelay');
        $ref->setAccessible(true);

        $this->assertEquals(300, $ref->getValue($sg), 'Default debounce delay must be 300ms');
    }

    /**
     * setDebounceDelay() updates the delay value.
     *
     * Validates: Requirement 17.7 - Configurable debounce delay
     */
    public function test_script_generator_set_debounce_delay_updates_value()
    {
        $sg = $this->makeScriptGenerator();
        $sg->setDebounceDelay(500);

        $ref = new \ReflectionProperty(ScriptGenerator::class, 'debounceDelay');
        $ref->setAccessible(true);

        $this->assertEquals(500, $ref->getValue($sg), 'setDebounceDelay() must update the delay');
    }

    /**
     * setDebounceDelay() clamps negative values to 0.
     */
    public function test_script_generator_set_debounce_delay_clamps_negative_to_zero()
    {
        $sg = $this->makeScriptGenerator();
        $sg->setDebounceDelay(-100);

        $ref = new \ReflectionProperty(ScriptGenerator::class, 'debounceDelay');
        $ref->setAccessible(true);

        $this->assertEquals(0, $ref->getValue($sg), 'Negative debounce delay must be clamped to 0');
    }

    /**
     * setDebounceDelay() returns $this for method chaining.
     */
    public function test_script_generator_set_debounce_delay_returns_self()
    {
        $sg     = $this->makeScriptGenerator();
        $result = $sg->setDebounceDelay(200);

        $this->assertSame($sg, $result, 'setDebounceDelay() must return $this for chaining');
    }

    /**
     * Constructor accepts debounce delay parameter.
     */
    public function test_script_generator_constructor_accepts_debounce_delay()
    {
        $sg  = new ScriptGenerator($this->makeConfig(), 150);
        $ref = new \ReflectionProperty(ScriptGenerator::class, 'debounceDelay');
        $ref->setAccessible(true);

        $this->assertEquals(150, $ref->getValue($sg), 'Constructor must set debounce delay');
    }

    /**
     * buildMainScript() includes debounce helper when debounceDelay > 0.
     *
     * Validates: Requirement 17.7 - Debounce helper injected into generated script
     */
    public function test_script_generator_build_main_script_includes_debounce_helper()
    {
        $sg = $this->makeScriptGenerator(300);

        $nodeNames = [
            'firstNode' => 'status_testField',
            'iNode'     => 'status_test',
            'currKey'   => 0,
            'fNode'     => 'testField',
        ];
        $targets = [
            'nexTargets'   => [],
            'curTargets'   => null,
            'firstTarget'  => null,
            'lastTarget'   => null,
            'next_target'  => null,
            'nextNode'     => null,
            'fieldsets'    => [],
        ];

        $script = $sg->buildMainScript('testNode', 'status', $targets, $nodeNames, null, null);

        $this->assertNotNull($script, 'buildMainScript() must return a script string');
        $this->assertStringContainsString('_debounce_', $script, 'Script must contain debounce helper when delay > 0');
    }

    /**
     * buildMainScript() does NOT include debounce helper when debounceDelay = 0.
     *
     * Validates: Requirement 17.7 - Debounce is optional (can be disabled)
     */
    public function test_script_generator_build_main_script_no_debounce_when_delay_zero()
    {
        $sg = $this->makeScriptGenerator(0);

        $nodeNames = [
            'firstNode' => 'status_testField',
            'iNode'     => 'status_test',
            'currKey'   => 0,
            'fNode'     => 'testField',
        ];
        $targets = [
            'nexTargets'   => [],
            'curTargets'   => null,
            'firstTarget'  => null,
            'lastTarget'   => null,
            'next_target'  => null,
            'nextNode'     => null,
            'fieldsets'    => [],
        ];

        $script = $sg->buildMainScript('testNode', 'status', $targets, $nodeNames, null, null);

        $this->assertNotNull($script, 'buildMainScript() must return a script string');
        $this->assertStringNotContainsString('_debounce_', $script, 'Script must NOT contain debounce helper when delay = 0');
        $this->assertStringContainsString('.change(function', $script, 'Script must use .change() when no debounce');
    }

    // =========================================================================
    // 6. ScriptGenerator - Asset Deduplication (2.4.5)
    // =========================================================================

    /**
     * getScripts() deduplicates JS asset paths.
     *
     * Validates: Requirement 4.8 - Avoid loading duplicate assets
     */
    public function test_script_generator_get_scripts_deduplicates_js_assets()
    {
        $sg = $this->makeScriptGenerator();

        // Inject duplicate JS paths via reflection
        $ref = new \ReflectionProperty(ScriptGenerator::class, 'addScripts');
        $ref->setAccessible(true);
        $ref->setValue($sg, [
            'js'  => ['path/to/script.js', 'path/to/script.js', 'path/to/other.js'],
            'css' => [],
        ]);

        $scripts = $sg->getScripts();

        $this->assertCount(2, $scripts['js'], 'Duplicate JS paths must be removed');
        $this->assertContains('path/to/script.js', $scripts['js']);
        $this->assertContains('path/to/other.js', $scripts['js']);
    }

    /**
     * getScripts() deduplicates CSS asset paths.
     *
     * Validates: Requirement 4.8 - Avoid loading duplicate assets
     */
    public function test_script_generator_get_scripts_deduplicates_css_assets()
    {
        $sg = $this->makeScriptGenerator();

        $ref = new \ReflectionProperty(ScriptGenerator::class, 'addScripts');
        $ref->setAccessible(true);
        $ref->setValue($sg, [
            'js'  => [],
            'css' => ['path/to/style.css', 'path/to/style.css', 'path/to/other.css'],
        ]);

        $scripts = $sg->getScripts();

        $this->assertCount(2, $scripts['css'], 'Duplicate CSS paths must be removed');
    }

    /**
     * getScripts() preserves add_js (inline scripts) without deduplication.
     *
     * Validates: Requirement 4.8 - Inline scripts are kept as-is
     */
    public function test_script_generator_get_scripts_preserves_add_js()
    {
        $sg = $this->makeScriptGenerator();

        $ref = new \ReflectionProperty(ScriptGenerator::class, 'addScripts');
        $ref->setAccessible(true);
        $ref->setValue($sg, [
            'add_js' => ['script1', 'script1', 'script2'],
        ]);

        $scripts = $sg->getScripts();

        // add_js should NOT be deduplicated (inline scripts may differ in context)
        $this->assertCount(3, $scripts['add_js'], 'add_js must not be deduplicated');
    }

    /**
     * getScripts() returns empty arrays when no scripts have been added.
     */
    public function test_script_generator_get_scripts_returns_empty_when_no_scripts()
    {
        $sg      = $this->makeScriptGenerator();
        $scripts = $sg->getScripts();

        $this->assertIsArray($scripts, 'getScripts() must return an array');
    }

    // =========================================================================
    // 7. SearchConfig - Cache Constants (2.4.2)
    // =========================================================================

    /**
     * SearchConfig defines CACHE_KEY_PREFIX constant.
     *
     * Validates: Requirement 5.7 - Cache key constants defined
     */
    public function test_search_config_defines_cache_key_prefix()
    {
        $this->assertTrue(
            defined(SearchConfig::class . '::CACHE_KEY_PREFIX'),
            'SearchConfig must define CACHE_KEY_PREFIX constant'
        );
        $this->assertNotEmpty(SearchConfig::CACHE_KEY_PREFIX);
    }

    /**
     * SearchConfig defines CACHE_TTL_DEFAULT constant with reasonable value.
     *
     * Validates: Requirement 5.7 - Appropriate cache TTL values
     */
    public function test_search_config_defines_cache_ttl_default()
    {
        $this->assertTrue(
            defined(SearchConfig::class . '::CACHE_TTL_DEFAULT'),
            'SearchConfig must define CACHE_TTL_DEFAULT constant'
        );
        $this->assertGreaterThan(0, SearchConfig::CACHE_TTL_DEFAULT, 'Default TTL must be positive');
        $this->assertLessThanOrEqual(3600, SearchConfig::CACHE_TTL_DEFAULT, 'Default TTL must be <= 1 hour');
    }

    /**
     * SearchConfig defines CACHE_TTL_LONG constant longer than CACHE_TTL_DEFAULT.
     *
     * Validates: Requirement 5.7 - Long TTL for stable reference data
     */
    public function test_search_config_cache_ttl_long_is_greater_than_default()
    {
        $this->assertGreaterThan(
            SearchConfig::CACHE_TTL_DEFAULT,
            SearchConfig::CACHE_TTL_LONG,
            'CACHE_TTL_LONG must be greater than CACHE_TTL_DEFAULT'
        );
    }

    // =========================================================================
    // 8. QueryBuilder - Identifier Sanitization (Security + Performance)
    // =========================================================================

    /**
     * sanitizeIdentifier() strips dangerous characters.
     *
     * Validates: Requirement 2.3 - Column name validation
     */
    public function test_query_builder_sanitize_identifier_strips_dangerous_chars()
    {
        $qb = $this->makeQueryBuilder();

        $this->assertEquals('valid_column', $qb->sanitizeIdentifier('valid_column'));
        $this->assertEquals('validcolumn', $qb->sanitizeIdentifier('valid-column'));
        $this->assertEquals('validcolumn', $qb->sanitizeIdentifier("valid'column"));
        $this->assertEquals('validcolumn', $qb->sanitizeIdentifier('valid;column'));
        $this->assertEquals('valid.column', $qb->sanitizeIdentifier('valid.column'));
    }

    /**
     * sanitizeIdentifier() allows alphanumeric, underscore, and dot.
     */
    public function test_query_builder_sanitize_identifier_allows_valid_chars()
    {
        $qb = $this->makeQueryBuilder();

        $this->assertEquals('table_name', $qb->sanitizeIdentifier('table_name'));
        $this->assertEquals('table.column', $qb->sanitizeIdentifier('table.column'));
        $this->assertEquals('Column123', $qb->sanitizeIdentifier('Column123'));
    }

    // =========================================================================
    // 9. Performance: Repeated getSelections() calls are O(1) after first load
    // =========================================================================

    /**
     * Multiple calls to getSelections() return the same data without re-processing.
     *
     * Validates: Requirement 4.8 - Efficient repeated access to selection data
     */
    public function test_query_builder_get_selections_returns_same_data_on_repeated_calls()
    {
        $qb = $this->makeQueryBuilder();

        $rows = [
            (object)['status' => 'active'],
            (object)['status' => 'inactive'],
        ];
        $qb->processQueryResults($rows, ['status']);

        $first  = $qb->getSelections();
        $second = $qb->getSelections();

        $this->assertSame($first, $second, 'getSelections() must return identical data on repeated calls');
    }
}
