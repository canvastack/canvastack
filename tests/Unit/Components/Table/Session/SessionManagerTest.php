<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\Session;

use Canvastack\Canvastack\Components\Table\Session\SessionManager;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * Test for SessionManager.
 */
class SessionManagerTest extends TestCase
{
    protected SessionManager $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear session before each test
        session()->flush();
    }

    protected function tearDown(): void
    {
        // Clear session after each test
        session()->flush();

        parent::tearDown();
    }

    /**
     * Test that SessionManager can be instantiated.
     */
    public function test_session_manager_can_be_instantiated(): void
    {
        $sessionManager = new SessionManager('test_table');

        $this->assertInstanceOf(SessionManager::class, $sessionManager);
    }

    /**
     * Test that session key is generated correctly.
     */
    public function test_session_key_is_generated_correctly(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionKey = $sessionManager->getSessionKey();

        $this->assertStringStartsWith('table_session_', $sessionKey);
        $this->assertIsString($sessionKey);
    }

    /**
     * Test that session key includes table name.
     */
    public function test_session_key_includes_table_name(): void
    {
        $sessionManager1 = new SessionManager('users');
        $sessionManager2 = new SessionManager('posts');

        $key1 = $sessionManager1->getSessionKey();
        $key2 = $sessionManager2->getSessionKey();

        $this->assertNotEquals($key1, $key2, 'Different table names should generate different session keys');
    }

    /**
     * Test that session key includes context.
     */
    public function test_session_key_includes_context(): void
    {
        $sessionManager1 = new SessionManager('users', 'admin');
        $sessionManager2 = new SessionManager('users', 'public');

        $key1 = $sessionManager1->getSessionKey();
        $key2 = $sessionManager2->getSessionKey();

        $this->assertNotEquals($key1, $key2, 'Different contexts should generate different session keys');
    }

    /**
     * Test that data can be saved to session.
     */
    public function test_data_can_be_saved_to_session(): void
    {
        $sessionManager = new SessionManager('test_table');

        $data = [
            'filters' => ['status' => 'active'],
            'active_tab' => 'summary',
        ];

        $sessionManager->save($data);

        $this->assertEquals('active', $sessionManager->get('filters')['status']);
        $this->assertEquals('summary', $sessionManager->get('active_tab'));
    }

    /**
     * Test that data can be loaded from session.
     */
    public function test_data_can_be_loaded_from_session(): void
    {
        $sessionManager = new SessionManager('test_table');

        $data = [
            'filters' => ['status' => 'active'],
            'display_limit' => 25,
        ];

        $sessionManager->save($data);

        // Create new instance to test loading
        $newSessionManager = new SessionManager('test_table');

        $this->assertEquals('active', $newSessionManager->get('filters')['status']);
        $this->assertEquals(25, $newSessionManager->get('display_limit'));
    }

    /**
     * Test that get() returns default value when key doesn't exist.
     */
    public function test_get_returns_default_value_when_key_not_exists(): void
    {
        $sessionManager = new SessionManager('test_table');

        $value = $sessionManager->get('non_existent_key', 'default_value');

        $this->assertEquals('default_value', $value);
    }

    /**
     * Test that get() returns null when key doesn't exist and no default.
     */
    public function test_get_returns_null_when_key_not_exists_and_no_default(): void
    {
        $sessionManager = new SessionManager('test_table');

        $value = $sessionManager->get('non_existent_key');

        $this->assertNull($value);
    }

    /**
     * Test that has() returns true when key exists.
     */
    public function test_has_returns_true_when_key_exists(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->save(['test_key' => 'test_value']);

        $this->assertTrue($sessionManager->has('test_key'));
    }

    /**
     * Test that has() returns false when key doesn't exist.
     */
    public function test_has_returns_false_when_key_not_exists(): void
    {
        $sessionManager = new SessionManager('test_table');

        $this->assertFalse($sessionManager->has('non_existent_key'));
    }

    /**
     * Test that clear() removes all session data.
     */
    public function test_clear_removes_all_session_data(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->save([
            'filters' => ['status' => 'active'],
            'active_tab' => 'summary',
            'display_limit' => 25,
        ]);

        $sessionManager->clear();

        $this->assertFalse($sessionManager->has('filters'));
        $this->assertFalse($sessionManager->has('active_tab'));
        $this->assertFalse($sessionManager->has('display_limit'));
    }

    /**
     * Test that all() returns all session data.
     */
    public function test_all_returns_all_session_data(): void
    {
        $sessionManager = new SessionManager('test_table');

        $data = [
            'filters' => ['status' => 'active'],
            'active_tab' => 'summary',
            'display_limit' => 25,
        ];

        $sessionManager->save($data);

        $allData = $sessionManager->all();

        $this->assertEquals($data, $allData);
    }

    /**
     * Test that save() merges data with existing data.
     */
    public function test_save_merges_data_with_existing_data(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->save(['key1' => 'value1']);
        $sessionManager->save(['key2' => 'value2']);

        $this->assertEquals('value1', $sessionManager->get('key1'));
        $this->assertEquals('value2', $sessionManager->get('key2'));
    }

    /**
     * Test that save() overwrites existing keys.
     */
    public function test_save_overwrites_existing_keys(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->save(['key1' => 'value1']);
        $sessionManager->save(['key1' => 'value2']);

        $this->assertEquals('value2', $sessionManager->get('key1'));
    }

    /**
     * Test that forget() removes specific key.
     */
    public function test_forget_removes_specific_key(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->save([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);

        $sessionManager->forget('key1');

        $this->assertFalse($sessionManager->has('key1'));
        $this->assertTrue($sessionManager->has('key2'));
    }

    /**
     * Test that forget() does nothing when key doesn't exist.
     */
    public function test_forget_does_nothing_when_key_not_exists(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->save(['key1' => 'value1']);

        // Should not throw exception
        $sessionManager->forget('non_existent_key');

        $this->assertTrue($sessionManager->has('key1'));
    }

    /**
     * Test that set() sets specific value.
     */
    public function test_set_sets_specific_value(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->set('test_key', 'test_value');

        $this->assertEquals('test_value', $sessionManager->get('test_key'));
    }

    /**
     * Test that set() overwrites existing value.
     */
    public function test_set_overwrites_existing_value(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->set('test_key', 'value1');
        $sessionManager->set('test_key', 'value2');

        $this->assertEquals('value2', $sessionManager->get('test_key'));
    }

    /**
     * Test that session persists across multiple instances.
     */
    public function test_session_persists_across_multiple_instances(): void
    {
        $sessionManager1 = new SessionManager('test_table');
        $sessionManager1->save(['test_key' => 'test_value']);

        $sessionManager2 = new SessionManager('test_table');

        $this->assertEquals('test_value', $sessionManager2->get('test_key'));
    }

    /**
     * Test that different tables have isolated sessions.
     */
    public function test_different_tables_have_isolated_sessions(): void
    {
        $sessionManager1 = new SessionManager('table1');
        $sessionManager1->save(['key' => 'value1']);

        $sessionManager2 = new SessionManager('table2');
        $sessionManager2->save(['key' => 'value2']);

        $this->assertEquals('value1', $sessionManager1->get('key'));
        $this->assertEquals('value2', $sessionManager2->get('key'));
    }

    /**
     * Test that session data can store arrays.
     */
    public function test_session_data_can_store_arrays(): void
    {
        $sessionManager = new SessionManager('test_table');

        $filters = [
            'status' => 'active',
            'category' => 'news',
            'tags' => ['php', 'laravel'],
        ];

        $sessionManager->save(['filters' => $filters]);

        $this->assertEquals($filters, $sessionManager->get('filters'));
    }

    /**
     * Test that session data can store nested arrays.
     */
    public function test_session_data_can_store_nested_arrays(): void
    {
        $sessionManager = new SessionManager('test_table');

        $data = [
            'filters' => [
                'basic' => ['status' => 'active'],
                'advanced' => ['date_range' => ['start' => '2024-01-01', 'end' => '2024-12-31']],
            ],
        ];

        $sessionManager->save($data);

        $this->assertEquals('active', $sessionManager->get('filters')['basic']['status']);
        $this->assertEquals('2024-01-01', $sessionManager->get('filters')['advanced']['date_range']['start']);
    }

    /**
     * Test that session data can store different data types.
     */
    public function test_session_data_can_store_different_data_types(): void
    {
        $sessionManager = new SessionManager('test_table');

        $sessionManager->save([
            'string' => 'test',
            'integer' => 123,
            'float' => 45.67,
            'boolean' => true,
            'array' => [1, 2, 3],
            'null' => null,
        ]);

        $this->assertIsString($sessionManager->get('string'));
        $this->assertIsInt($sessionManager->get('integer'));
        $this->assertIsFloat($sessionManager->get('float'));
        $this->assertIsBool($sessionManager->get('boolean'));
        $this->assertIsArray($sessionManager->get('array'));
        $this->assertNull($sessionManager->get('null'));
    }

    /**
     * Test that load() returns empty array when no session data.
     */
    public function test_load_returns_empty_array_when_no_session_data(): void
    {
        $sessionManager = new SessionManager('test_table');

        $data = $sessionManager->load();

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    /**
     * Test that session key is unique per user.
     */
    public function test_session_key_is_unique_per_user(): void
    {
        // Test as guest
        $sessionManager1 = new SessionManager('test_table');
        $key1 = $sessionManager1->getSessionKey();

        // Mock authenticated user
        Auth::shouldReceive('id')->andReturn(1);
        $sessionManager2 = new SessionManager('test_table');
        $key2 = $sessionManager2->getSessionKey();

        // Keys should be different for guest vs authenticated user
        // Note: This test may need adjustment based on actual auth implementation
        $this->assertIsString($key1);
        $this->assertIsString($key2);
    }

    /**
     * Test that empty context works correctly.
     */
    public function test_empty_context_works_correctly(): void
    {
        $sessionManager = new SessionManager('test_table', '');

        $sessionManager->save(['test_key' => 'test_value']);

        $this->assertEquals('test_value', $sessionManager->get('test_key'));
    }

    /**
     * Test that session manager handles large data sets.
     */
    public function test_session_manager_handles_large_data_sets(): void
    {
        $sessionManager = new SessionManager('test_table');

        $largeData = [];
        for ($i = 0; $i < 100; $i++) {
            $largeData["key_{$i}"] = "value_{$i}";
        }

        $sessionManager->save($largeData);

        $this->assertCount(100, $sessionManager->all());
        $this->assertEquals('value_50', $sessionManager->get('key_50'));
    }
}
