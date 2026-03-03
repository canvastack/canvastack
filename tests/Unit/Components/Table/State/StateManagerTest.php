<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table\State;

use Canvastack\Canvastack\Components\Table\State\StateManager;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for StateManager.
 *
 * @covers \Canvastack\Canvastack\Components\Table\State\StateManager
 */
class StateManagerTest extends TestCase
{
    protected StateManager $stateManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stateManager = new StateManager();
    }

    /**
     * Test that state can be saved and retrieved.
     */
    public function test_save_and_get_state(): void
    {
        // Arrange
        $key = 'test_key';
        $value = 'test_value';

        // Act
        $this->stateManager->saveState($key, $value);
        $result = $this->stateManager->getState($key);

        // Assert
        $this->assertEquals($value, $result);
    }

    /**
     * Test that getState returns default when key doesn't exist.
     */
    public function test_get_state_returns_default(): void
    {
        // Arrange
        $key = 'non_existent_key';
        $default = 'default_value';

        // Act
        $result = $this->stateManager->getState($key, $default);

        // Assert
        $this->assertEquals($default, $result);
    }

    /**
     * Test that hasState correctly identifies existing keys.
     */
    public function test_has_state(): void
    {
        // Arrange
        $key = 'test_key';
        $value = 'test_value';

        // Act
        $this->stateManager->saveState($key, $value);

        // Assert
        $this->assertTrue($this->stateManager->hasState($key));
        $this->assertFalse($this->stateManager->hasState('non_existent_key'));
    }

    /**
     * Test that state history is tracked correctly.
     */
    public function test_state_history_tracking(): void
    {
        // Arrange
        $key = 'test_key';
        $value1 = 'value1';
        $value2 = 'value2';

        // Act
        $this->stateManager->saveState($key, $value1);
        $this->stateManager->saveState($key, $value2);
        $history = $this->stateManager->getStateHistory();

        // Assert
        $this->assertCount(2, $history);
        $this->assertEquals($key, $history[0]['key']);
        $this->assertNull($history[0]['old']);
        $this->assertEquals($value1, $history[0]['new']);
        $this->assertEquals($value1, $history[1]['old']);
        $this->assertEquals($value2, $history[1]['new']);
        $this->assertIsFloat($history[0]['timestamp']);
    }

    /**
     * Test that clearVar removes specific state variable.
     */
    public function test_clear_var(): void
    {
        // Arrange
        $key1 = 'key1';
        $key2 = 'key2';
        $this->stateManager->saveState($key1, 'value1');
        $this->stateManager->saveState($key2, 'value2');

        // Act
        $this->stateManager->clearVar($key1);

        // Assert
        $this->assertFalse($this->stateManager->hasState($key1));
        $this->assertTrue($this->stateManager->hasState($key2));
    }

    /**
     * Test that clearAll removes all state variables.
     */
    public function test_clear_all(): void
    {
        // Arrange
        $this->stateManager->saveState('key1', 'value1');
        $this->stateManager->saveState('key2', 'value2');
        $this->stateManager->saveState('key3', 'value3');

        // Act
        $this->stateManager->clearAll();

        // Assert
        $this->assertFalse($this->stateManager->hasState('key1'));
        $this->assertFalse($this->stateManager->hasState('key2'));
        $this->assertFalse($this->stateManager->hasState('key3'));
        $this->assertEmpty($this->stateManager->getAllState());
    }

    /**
     * Test that clearClearableVars only clears defined clearable variables.
     */
    public function test_clear_clearable_vars(): void
    {
        // Arrange
        $this->stateManager->saveState('merged_columns', ['col1', 'col2']);
        $this->stateManager->saveState('fixed_columns', [2, 1]);
        $this->stateManager->saveState('custom_var', 'custom_value');

        // Act
        $this->stateManager->clearClearableVars();

        // Assert
        $this->assertFalse($this->stateManager->hasState('merged_columns'));
        $this->assertFalse($this->stateManager->hasState('fixed_columns'));
        $this->assertTrue($this->stateManager->hasState('custom_var'));
    }

    /**
     * Test that getAllState returns all current state.
     */
    public function test_get_all_state(): void
    {
        // Arrange
        $state = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        foreach ($state as $key => $value) {
            $this->stateManager->saveState($key, $value);
        }

        // Act
        $result = $this->stateManager->getAllState();

        // Assert
        $this->assertEquals($state, $result);
    }

    /**
     * Test that getClearableVars returns the list of clearable variables.
     */
    public function test_get_clearable_vars(): void
    {
        // Act
        $clearableVars = $this->stateManager->getClearableVars();

        // Assert
        $this->assertIsArray($clearableVars);
        $this->assertContains('merged_columns', $clearableVars);
        $this->assertContains('fixed_columns', $clearableVars);
        $this->assertContains('hidden_columns', $clearableVars);
        $this->assertContains('formats', $clearableVars);
        $this->assertContains('conditions', $clearableVars);
        $this->assertContains('alignments', $clearableVars);
        $this->assertContains('filters', $clearableVars);
    }

    /**
     * Test that addClearableVar adds a new clearable variable.
     */
    public function test_add_clearable_var(): void
    {
        // Arrange
        $newVar = 'custom_clearable_var';

        // Act
        $this->stateManager->addClearableVar($newVar);
        $clearableVars = $this->stateManager->getClearableVars();

        // Assert
        $this->assertContains($newVar, $clearableVars);
    }

    /**
     * Test that addClearableVar doesn't add duplicates.
     */
    public function test_add_clearable_var_no_duplicates(): void
    {
        // Arrange
        $var = 'merged_columns';
        $initialCount = count($this->stateManager->getClearableVars());

        // Act
        $this->stateManager->addClearableVar($var);
        $finalCount = count($this->stateManager->getClearableVars());

        // Assert
        $this->assertEquals($initialCount, $finalCount);
    }

    /**
     * Test that removeClearableVar removes a clearable variable.
     */
    public function test_remove_clearable_var(): void
    {
        // Arrange
        $varToRemove = 'merged_columns';

        // Act
        $this->stateManager->removeClearableVar($varToRemove);
        $clearableVars = $this->stateManager->getClearableVars();

        // Assert
        $this->assertNotContains($varToRemove, $clearableVars);
    }

    /**
     * Test that clearHistory removes state history.
     */
    public function test_clear_history(): void
    {
        // Arrange
        $this->stateManager->saveState('key1', 'value1');
        $this->stateManager->saveState('key2', 'value2');
        $this->assertNotEmpty($this->stateManager->getStateHistory());

        // Act
        $this->stateManager->clearHistory();

        // Assert
        $this->assertEmpty($this->stateManager->getStateHistory());
    }

    /**
     * Test that state can store different data types.
     */
    public function test_state_stores_different_types(): void
    {
        // Arrange & Act
        $this->stateManager->saveState('string', 'test');
        $this->stateManager->saveState('integer', 123);
        $this->stateManager->saveState('float', 123.45);
        $this->stateManager->saveState('boolean', true);
        $this->stateManager->saveState('array', ['a', 'b', 'c']);
        $this->stateManager->saveState('null', null);

        // Assert
        $this->assertIsString($this->stateManager->getState('string'));
        $this->assertIsInt($this->stateManager->getState('integer'));
        $this->assertIsFloat($this->stateManager->getState('float'));
        $this->assertIsBool($this->stateManager->getState('boolean'));
        $this->assertIsArray($this->stateManager->getState('array'));
        $this->assertNull($this->stateManager->getState('null'));
    }

    /**
     * Test that state history timestamps are sequential.
     */
    public function test_state_history_timestamps_sequential(): void
    {
        // Arrange & Act
        $this->stateManager->saveState('key1', 'value1');
        usleep(1000); // 1ms delay
        $this->stateManager->saveState('key2', 'value2');
        usleep(1000); // 1ms delay
        $this->stateManager->saveState('key3', 'value3');

        $history = $this->stateManager->getStateHistory();

        // Assert
        $this->assertGreaterThan($history[0]['timestamp'], $history[1]['timestamp']);
        $this->assertGreaterThan($history[1]['timestamp'], $history[2]['timestamp']);
    }

    /**
     * Test that clearVar handles non-existent keys gracefully.
     */
    public function test_clear_var_non_existent_key(): void
    {
        // Act & Assert - Should not throw exception
        $this->stateManager->clearVar('non_existent_key');
        $this->assertTrue(true); // If we reach here, no exception was thrown
    }

    /**
     * Test state isolation between instances.
     */
    public function test_state_isolation_between_instances(): void
    {
        // Arrange
        $manager1 = new StateManager();
        $manager2 = new StateManager();

        // Act
        $manager1->saveState('key', 'value1');
        $manager2->saveState('key', 'value2');

        // Assert
        $this->assertEquals('value1', $manager1->getState('key'));
        $this->assertEquals('value2', $manager2->getState('key'));
    }

    /**
     * Test that state can be overwritten.
     */
    public function test_state_can_be_overwritten(): void
    {
        // Arrange
        $key = 'test_key';
        $value1 = 'value1';
        $value2 = 'value2';

        // Act
        $this->stateManager->saveState($key, $value1);
        $this->assertEquals($value1, $this->stateManager->getState($key));

        $this->stateManager->saveState($key, $value2);
        $this->assertEquals($value2, $this->stateManager->getState($key));

        // Assert
        $history = $this->stateManager->getStateHistory();
        $this->assertCount(2, $history);
    }
}
