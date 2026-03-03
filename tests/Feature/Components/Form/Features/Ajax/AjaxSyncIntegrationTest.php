<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Ajax;

use Canvastack\Canvastack\Components\Form\Features\Ajax\AjaxSync;
use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Http\Controllers\AjaxSyncController;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Integration tests for Ajax Sync workflow.
 *
 * Tests the complete cascading dropdown flow from field registration
 * to Ajax request handling and target field population.
 *
 * **Validates: Requirements 2.1, 2.2, 2.6, 2.7, 2.8**
 */
class AjaxSyncIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected AjaxSync $ajaxSync;

    protected QueryEncryption $encryption;

    protected AjaxSyncController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encryption = new QueryEncryption(app('encrypter'));
        $this->ajaxSync = new AjaxSync($this->encryption);
        $this->controller = new AjaxSyncController($this->encryption);

        // Register the Ajax sync route
        Route::post('/ajax/sync', [AjaxSyncController::class, 'handle'])
            ->name('canvastack.ajax.sync');

        // Create test database tables
        $this->createTestTables();
    }

    protected function createTestTables(\Illuminate\Database\Capsule\Manager $capsule = null): void
    {
        DB::statement('CREATE TABLE IF NOT EXISTS provinces (
            id INT PRIMARY KEY,
            name VARCHAR(255)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS cities (
            id INT PRIMARY KEY,
            province_id INT,
            name VARCHAR(255)
        )');

        DB::statement('CREATE TABLE IF NOT EXISTS districts (
            id INT PRIMARY KEY,
            city_id INT,
            name VARCHAR(255)
        )');

        // Insert test data
        DB::table('provinces')->insert([
            ['id' => 1, 'name' => 'Province A'],
            ['id' => 2, 'name' => 'Province B'],
        ]);

        DB::table('cities')->insert([
            ['id' => 1, 'province_id' => 1, 'name' => 'City A1'],
            ['id' => 2, 'province_id' => 1, 'name' => 'City A2'],
            ['id' => 3, 'province_id' => 2, 'name' => 'City B1'],
        ]);

        DB::table('districts')->insert([
            ['id' => 1, 'city_id' => 1, 'name' => 'District A1-1'],
            ['id' => 2, 'city_id' => 1, 'name' => 'District A1-2'],
            ['id' => 3, 'city_id' => 2, 'name' => 'District A2-1'],
        ]);
    }

    /**
     * Test complete cascading dropdown flow.
     *
     * **Validates: Requirement 2.1** - Register cascading relationship
     * **Validates: Requirement 2.2** - Ajax request on source field change
     */
    public function test_complete_cascading_dropdown_flow(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();

        $this->assertCount(1, $relationships);
        $this->assertEquals('province_id', $relationships[0]['source']);
        $this->assertEquals('city_id', $relationships[0]['target']);

        // Verify JavaScript is generated
        $script = $this->ajaxSync->renderScript();
        $this->assertStringContainsString('province_id', $script);
        $this->assertStringContainsString('city_id', $script);
        $this->assertStringContainsString('addEventListener', $script);
    }

    /**
     * Test source field change triggers Ajax request.
     *
     * **Validates: Requirement 2.2** - Ajax request on source field change
     * **Validates: Requirement 2.6** - Return JSON response with options
     */
    public function test_source_field_change_triggers_ajax_request(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // Simulate Ajax request
        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 1,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $data = $response->json('data');
        $this->assertArrayHasKey('options', $data);
        $this->assertIsArray($data['options']);
    }

    /**
     * Test target field population with correct options.
     *
     * **Validates: Requirement 2.7** - Populate target field with returned options
     */
    public function test_target_field_population(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // Request cities for province 1
        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 1,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $options = $data['options'];

        // Should return 2 cities for province 1
        $this->assertCount(2, $options);
        $this->assertEquals(1, $options[0]['value']);
        $this->assertEquals('City A1', $options[0]['label']);
        $this->assertEquals(2, $options[1]['value']);
        $this->assertEquals('City A2', $options[1]['label']);
    }

    /**
     * Test error handling for invalid source value.
     *
     * **Validates: Requirement 2.8** - Display error message on Ajax failure
     */
    public function test_error_handling_for_invalid_source_value(): void
    {
        // Register sync relationship
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // Request cities for non-existent province
        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationship,
            'sourceValue' => 999,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $options = $data['options'];

        // Should return empty array for non-existent province
        $this->assertCount(0, $options);
    }

    /**
     * Test error handling for missing relationship data.
     *
     * **Validates: Requirement 2.8** - Display error message on Ajax failure
     */
    public function test_error_handling_for_missing_relationship_data(): void
    {
        // Send request without relationship data
        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'sourceValue' => 1,
        ]);

        $response->assertStatus(422); // Validation error
        $response->assertJsonValidationErrors(['relationship']);
    }

    /**
     * Test error handling for invalid encrypted data.
     *
     * **Validates: Requirement 2.8** - Display error message on Ajax failure
     */
    public function test_error_handling_for_invalid_encrypted_data(): void
    {
        // Send request with invalid encrypted data
        $response = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => [
                'source' => 'province_id',
                'target' => 'city_id',
                'values' => 'invalid_encrypted_data',
                'labels' => 'invalid_encrypted_data',
                'query' => 'invalid_encrypted_data',
            ],
            'sourceValue' => 1,
        ]);

        $response->assertStatus(400); // Bad request
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid encrypted data',
        ]);
    }

    /**
     * Test multi-level cascading (Province → City → District).
     *
     * **Validates: Requirement 2.11** - Support multiple cascading levels
     */
    public function test_multi_level_cascading(): void
    {
        // Register first level: Province → City
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        // Register second level: City → District
        $this->ajaxSync->register(
            'city_id',
            'district_id',
            'id',
            'name',
            'SELECT id, name FROM districts WHERE city_id = ?',
            null
        );

        $relationships = $this->ajaxSync->getRelationships();
        $this->assertCount(2, $relationships);

        // Test first level: Get cities for province 1
        $response1 = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationships[0],
            'sourceValue' => 1,
        ]);

        $response1->assertStatus(200);
        $cities = $response1->json('data.options');
        $this->assertCount(2, $cities);

        // Test second level: Get districts for city 1
        $response2 = $this->postJson(route('canvastack.ajax.sync'), [
            'relationship' => $relationships[1],
            'sourceValue' => 1,
        ]);

        $response2->assertStatus(200);
        $districts = $response2->json('data.options');
        $this->assertCount(2, $districts);
        $this->assertEquals('District A1-1', $districts[0]['label']);
        $this->assertEquals('District A1-2', $districts[1]['label']);
    }

    /**
     * Test JavaScript generation includes all relationships.
     */
    public function test_javascript_generation_includes_all_relationships(): void
    {
        // Register multiple relationships
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $this->ajaxSync->register(
            'city_id',
            'district_id',
            'id',
            'name',
            'SELECT id, name FROM districts WHERE city_id = ?',
            null
        );

        $script = $this->ajaxSync->renderScript();

        // Verify script contains both relationships
        $this->assertStringContainsString('province_id', $script);
        $this->assertStringContainsString('city_id', $script);
        $this->assertStringContainsString('district_id', $script);

        // Verify script contains dependency map for cascading
        $this->assertStringContainsString('dependencyMap', $script);
        $this->assertStringContainsString('resetDependentFields', $script);
    }

    /**
     * Test target field is disabled until source has value.
     *
     * **Validates: Requirement 2.9** - Disable target field until source has value
     */
    public function test_target_field_disabled_state_in_javascript(): void
    {
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $script = $this->ajaxSync->renderScript();

        // Verify script disables target field initially
        $this->assertStringContainsString('targetField.disabled = true', $script);

        // Verify script enables target field after successful load
        $this->assertStringContainsString('targetField.disabled = false', $script);
    }

    /**
     * Test loading indicator is shown during Ajax request.
     *
     * **Validates: Requirement 2.14** - Provide loading indicator on target field
     */
    public function test_loading_indicator_in_javascript(): void
    {
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            null
        );

        $script = $this->ajaxSync->renderScript();

        // Verify script shows loading indicator
        $this->assertStringContainsString('loading', $script);
        $this->assertStringContainsString('Loading...', $script);

        // Verify script removes loading indicator after response
        $this->assertStringContainsString('classList.remove', $script);
    }

    /**
     * Test pre-selection support.
     *
     * **Validates: Requirement 2.10** - Pre-select option when selected value provided
     */
    public function test_pre_selection_support(): void
    {
        // Register with selected value
        $this->ajaxSync->register(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?',
            2 // Pre-select city with id 2
        );

        $relationships = $this->ajaxSync->getRelationships();
        $relationship = $relationships[0];

        // Verify selected value is encrypted in relationship
        $this->assertArrayHasKey('selected', $relationship);

        // Verify JavaScript handles pre-selection
        $script = $this->ajaxSync->renderScript();
        $this->assertStringContainsString('selected', $script);
        $this->assertStringContainsString('opt.selected = true', $script);
    }

    protected function tearDown(): void
    {
        // Clean up test tables
        DB::statement('DROP TABLE IF EXISTS districts');
        DB::statement('DROP TABLE IF EXISTS cities');
        DB::statement('DROP TABLE IF EXISTS provinces');

        parent::tearDown();
    }
}
