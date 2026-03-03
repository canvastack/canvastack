<?php

namespace Canvastack\Canvastack\Tests\Unit\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\AjaxSync;
use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Property Tests for Ajax Sync Backward Compatibility.
 *
 * Tests Requirements: 2.18, 18.5
 *
 * Property 8: Ajax Sync Backward Compatibility
 * - Legacy sync() method signature must work identically to original implementation
 * - All parameter combinations must be supported
 * - Registered relationships must be retrievable
 * - Multiple sync relationships can coexist
 * - Query normalization must handle all SQL variations
 * - Selected values (null, empty, numeric) must be handled correctly
 * - JavaScript rendering must match original format
 * - Duplicate registrations must be handled gracefully
 * - Special field names (with dots, brackets) must work
 * - Complex queries (joins, subqueries) must be supported
 */
class AjaxSyncBackwardCompatibilityPropertyTest extends TestCase
{
    protected AjaxSync $ajaxSync;

    protected QueryEncryption $encryption;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryption = new QueryEncryption(app('encrypter'));
        $this->ajaxSync = new AjaxSync($this->encryption);
    }

    /**
     * Property: Legacy sync() method signature must work identically.
     *
     * Validates Requirement 2.18 (Backward compatibility)
     *
     * @test
     */
    public function test_legacy_sync_method_signature_works(): void
    {
        // Legacy signature: sync($field, $query, $selected = null)
        $field = 'city_id';
        $query = 'SELECT id, name FROM cities WHERE province_id = ?';
        $selected = 5;

        // Should not throw any exceptions
        $this->ajaxSync->sync($field, $query, $selected);

        // Should register the relationship
        $registered = $this->ajaxSync->getRegisteredRelationships();

        $this->assertArrayHasKey($field, $registered);
        $this->assertEquals($selected, $registered[$field]['selected']);
    }

    /**
     * Property: All legacy parameter combinations must be supported.
     *
     * @test
     */
    public function test_all_legacy_parameter_combinations_supported(): void
    {
        $testCases = [
            // [field, query, selected]
            ['city_id', 'SELECT id, name FROM cities', null],
            ['city_id', 'SELECT id, name FROM cities', ''],
            ['city_id', 'SELECT id, name FROM cities', 0],
            ['city_id', 'SELECT id, name FROM cities', 1],
            ['city_id', 'SELECT id, name FROM cities', '5'],
            ['city_id', 'SELECT id, name FROM cities WHERE active = 1', 10],
        ];

        foreach ($testCases as [$field, $query, $selected]) {
            $ajaxSync = new AjaxSync($this->encryption);
            $ajaxSync->sync($field, $query, $selected);

            $registered = $ajaxSync->getRegisteredRelationships();

            $this->assertArrayHasKey($field, $registered);
            $this->assertEquals($selected, $registered[$field]['selected']);
        }
    }

    /**
     * Property: Registered relationships must be retrievable.
     *
     * Validates Requirement 18.5 (Relationship registration)
     *
     * @test
     */
    public function test_registered_relationships_are_retrievable(): void
    {
        $field1 = 'province_id';
        $query1 = 'SELECT id, name FROM provinces';
        $selected1 = 3;

        $field2 = 'city_id';
        $query2 = 'SELECT id, name FROM cities WHERE province_id = ?';
        $selected2 = 15;

        $this->ajaxSync->sync($field1, $query1, $selected1);
        $this->ajaxSync->sync($field2, $query2, $selected2);

        $registered = $this->ajaxSync->getRegisteredRelationships();

        $this->assertCount(2, $registered);
        $this->assertArrayHasKey($field1, $registered);
        $this->assertArrayHasKey($field2, $registered);

        $this->assertEquals($selected1, $registered[$field1]['selected']);
        $this->assertEquals($selected2, $registered[$field2]['selected']);
    }

    /**
     * Property: Multiple sync relationships can coexist.
     *
     * @test
     */
    public function test_multiple_sync_relationships_coexist(): void
    {
        $relationships = [
            'province_id' => ['SELECT id, name FROM provinces', 1],
            'city_id' => ['SELECT id, name FROM cities WHERE province_id = ?', 5],
            'district_id' => ['SELECT id, name FROM districts WHERE city_id = ?', 10],
            'category_id' => ['SELECT id, name FROM categories', 2],
        ];

        foreach ($relationships as $field => [$query, $selected]) {
            $this->ajaxSync->sync($field, $query, $selected);
        }

        $registered = $this->ajaxSync->getRegisteredRelationships();

        $this->assertCount(4, $registered);

        foreach ($relationships as $field => [$query, $selected]) {
            $this->assertArrayHasKey($field, $registered);
            $this->assertEquals($selected, $registered[$field]['selected']);
        }
    }

    /**
     * Property: Query normalization must handle all SQL variations.
     *
     * @test
     */
    public function test_query_normalization_handles_sql_variations(): void
    {
        $queries = [
            'SELECT id, name FROM cities',
            'select id, name from cities',
            'SELECT  id,  name  FROM  cities',
            "SELECT\nid,\nname\nFROM\ncities",
            "SELECT\tid,\tname\tFROM\tcities",
            'SELECT id, name FROM cities WHERE active = 1',
            'SELECT id, name FROM cities WHERE province_id = ? AND active = 1',
        ];

        foreach ($queries as $query) {
            $ajaxSync = new AjaxSync($this->encryption);
            $ajaxSync->sync('city_id', $query, null);

            $registered = $ajaxSync->getRegisteredRelationships();

            $this->assertArrayHasKey('city_id', $registered);
            $this->assertNotEmpty($registered['city_id']['encrypted_query']);
        }
    }

    /**
     * Property: Null selected values must be handled correctly.
     *
     * @test
     */
    public function test_null_selected_values_handled_correctly(): void
    {
        $this->ajaxSync->sync('city_id', 'SELECT id, name FROM cities', null);

        $registered = $this->ajaxSync->getRegisteredRelationships();

        $this->assertArrayHasKey('city_id', $registered);
        $this->assertNull($registered['city_id']['selected']);
    }

    /**
     * Property: Empty string selected values must be handled correctly.
     *
     * @test
     */
    public function test_empty_string_selected_values_handled_correctly(): void
    {
        $this->ajaxSync->sync('city_id', 'SELECT id, name FROM cities', '');

        $registered = $this->ajaxSync->getRegisteredRelationships();

        $this->assertArrayHasKey('city_id', $registered);
        $this->assertEquals('', $registered['city_id']['selected']);
    }

    /**
     * Property: Numeric selected values must be handled correctly.
     *
     * @test
     */
    public function test_numeric_selected_values_handled_correctly(): void
    {
        $numericValues = [0, 1, 42, -1, 999];

        foreach ($numericValues as $value) {
            $ajaxSync = new AjaxSync($this->encryption);
            $ajaxSync->sync('city_id', 'SELECT id, name FROM cities', $value);

            $registered = $ajaxSync->getRegisteredRelationships();

            $this->assertArrayHasKey('city_id', $registered);
            $this->assertEquals($value, $registered['city_id']['selected']);
        }
    }

    /**
     * Property: JavaScript rendering must match original format.
     *
     * Validates Requirement 2.18 (Backward compatibility)
     *
     * @test
     */
    public function test_javascript_rendering_matches_original_format(): void
    {
        $this->ajaxSync->sync('city_id', 'SELECT id, name FROM cities WHERE province_id = ?', 5);

        $js = $this->ajaxSync->renderJavaScript();

        // Should contain field name
        $this->assertStringContainsString('city_id', $js);

        // Should contain encrypted query
        $this->assertStringContainsString('encrypted_query', $js);

        // Should contain selected value
        $this->assertStringContainsString('5', $js);

        // Should be valid JavaScript
        $this->assertStringContainsString('var ajaxSyncConfig', $js);
    }

    /**
     * Property: Duplicate registrations must be handled gracefully.
     *
     * @test
     */
    public function test_duplicate_registrations_handled_gracefully(): void
    {
        // Register same field twice with different queries
        $this->ajaxSync->sync('city_id', 'SELECT id, name FROM cities', 5);
        $this->ajaxSync->sync('city_id', 'SELECT id, name FROM cities WHERE active = 1', 10);

        $registered = $this->ajaxSync->getRegisteredRelationships();

        // Should only have one registration (last one wins)
        $this->assertCount(1, $registered);
        $this->assertArrayHasKey('city_id', $registered);

        // Should use the last registered values
        $this->assertEquals(10, $registered['city_id']['selected']);
    }

    /**
     * Property: Special field names with dots must work.
     *
     * @test
     */
    public function test_special_field_names_with_dots_work(): void
    {
        $fieldNames = [
            'location.city_id',
            'address.province_id',
            'user.profile.city_id',
        ];

        foreach ($fieldNames as $field) {
            $ajaxSync = new AjaxSync($this->encryption);
            $ajaxSync->sync($field, 'SELECT id, name FROM cities', 5);

            $registered = $ajaxSync->getRegisteredRelationships();

            $this->assertArrayHasKey($field, $registered);
        }
    }

    /**
     * Property: Special field names with brackets must work.
     *
     * @test
     */
    public function test_special_field_names_with_brackets_work(): void
    {
        $fieldNames = [
            'location[city_id]',
            'address[province_id]',
            'user[profile][city_id]',
        ];

        foreach ($fieldNames as $field) {
            $ajaxSync = new AjaxSync($this->encryption);
            $ajaxSync->sync($field, 'SELECT id, name FROM cities', 5);

            $registered = $ajaxSync->getRegisteredRelationships();

            $this->assertArrayHasKey($field, $registered);
        }
    }

    /**
     * Property: Complex queries with joins must be supported.
     *
     * @test
     */
    public function test_complex_queries_with_joins_supported(): void
    {
        $complexQueries = [
            'SELECT c.id, c.name FROM cities c JOIN provinces p ON c.province_id = p.id',
            'SELECT c.id, CONCAT(c.name, " - ", p.name) as name FROM cities c LEFT JOIN provinces p ON c.province_id = p.id',
            'SELECT id, name FROM cities WHERE id IN (SELECT city_id FROM districts WHERE active = 1)',
        ];

        foreach ($complexQueries as $query) {
            $ajaxSync = new AjaxSync($this->encryption);
            $ajaxSync->sync('city_id', $query, null);

            $registered = $ajaxSync->getRegisteredRelationships();

            $this->assertArrayHasKey('city_id', $registered);
            $this->assertNotEmpty($registered['city_id']['encrypted_query']);
        }
    }

    /**
     * Property: Complete backward compatibility validation.
     *
     * This test validates that the entire legacy workflow works end-to-end
     *
     * Validates Requirements 2.18, 18.5
     *
     * @test
     */
    public function test_complete_backward_compatibility_validation(): void
    {
        // Simulate legacy usage pattern
        $ajaxSync = new AjaxSync($this->encryption);

        // Step 1: Register multiple relationships (legacy style)
        $ajaxSync->sync('province_id', 'SELECT id, name FROM provinces', 3);
        $ajaxSync->sync('city_id', 'SELECT id, name FROM cities WHERE province_id = ?', 15);
        $ajaxSync->sync('district_id', 'SELECT id, name FROM districts WHERE city_id = ?', null);

        // Step 2: Verify all relationships are registered
        $registered = $ajaxSync->getRegisteredRelationships();

        $this->assertCount(3, $registered);
        $this->assertArrayHasKey('province_id', $registered);
        $this->assertArrayHasKey('city_id', $registered);
        $this->assertArrayHasKey('district_id', $registered);

        // Step 3: Verify selected values
        $this->assertEquals(3, $registered['province_id']['selected']);
        $this->assertEquals(15, $registered['city_id']['selected']);
        $this->assertNull($registered['district_id']['selected']);

        // Step 4: Verify queries are encrypted
        $this->assertNotEmpty($registered['province_id']['encrypted_query']);
        $this->assertNotEmpty($registered['city_id']['encrypted_query']);
        $this->assertNotEmpty($registered['district_id']['encrypted_query']);

        // Step 5: Verify JavaScript can be rendered
        $js = $ajaxSync->renderJavaScript();

        $this->assertNotEmpty($js);
        $this->assertStringContainsString('province_id', $js);
        $this->assertStringContainsString('city_id', $js);
        $this->assertStringContainsString('district_id', $js);

        // Step 6: Verify encrypted queries can be decrypted
        foreach ($registered as $field => $data) {
            $decrypted = $this->encryption->decrypt($data['encrypted_query']);
            $this->assertNotEmpty($decrypted);
            $this->assertStringContainsString('SELECT', strtoupper($decrypted));
        }
    }
}
