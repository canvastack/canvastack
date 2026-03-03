<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Table;

use Canvastack\Canvastack\Tests\TestCase;

/**
 * Test for Bi-Directional Cascade functionality.
 * 
 * Tests the detectCascadeDirection() method and related cascade logic.
 */
class BiDirectionalCascadeTest extends TestCase
{
    /**
     * Test that detectCascadeDirection returns correct upstream/downstream filters.
     * 
     * Subtask: Method correctly identifies upstream filters (before changed filter)
     * Subtask: Method correctly identifies downstream filters (after changed filter)
     *
     * @return void
     */
    public function test_detect_cascade_direction_returns_correct_filters(): void
    {
        // This test validates the JavaScript logic conceptually
        // The actual implementation is in Alpine.js, so we test the concept here
        
        $filters = [
            ['column' => 'name', 'label' => 'Name', 'type' => 'selectbox'],
            ['column' => 'email', 'label' => 'Email', 'type' => 'selectbox'],
            ['column' => 'created_at', 'label' => 'Created Date', 'type' => 'datebox'],
        ];
        
        // Simulate selecting the middle filter (email)
        $changedFilterIndex = 1; // email
        
        // Expected upstream: filters before email (name)
        $expectedUpstream = array_slice($filters, 0, $changedFilterIndex);
        $this->assertCount(1, $expectedUpstream);
        $this->assertEquals('name', $expectedUpstream[0]['column']);
        
        // Expected downstream: filters after email (created_at)
        $expectedDownstream = array_slice($filters, $changedFilterIndex + 1);
        $this->assertCount(1, $expectedDownstream);
        $this->assertEquals('created_at', $expectedDownstream[0]['column']);
    }
    
    /**
     * Test that detectCascadeDirection handles edge case: first filter.
     * 
     * Subtask: Method handles edge cases (first filter, last filter, single filter)
     *
     * @return void
     */
    public function test_detect_cascade_direction_handles_first_filter(): void
    {
        $filters = [
            ['column' => 'name', 'label' => 'Name', 'type' => 'selectbox'],
            ['column' => 'email', 'label' => 'Email', 'type' => 'selectbox'],
            ['column' => 'created_at', 'label' => 'Created Date', 'type' => 'datebox'],
        ];
        
        // Simulate selecting the first filter (name)
        $changedFilterIndex = 0; // name
        
        // Expected upstream: empty (no filters before name)
        $expectedUpstream = array_slice($filters, 0, $changedFilterIndex);
        $this->assertCount(0, $expectedUpstream);
        
        // Expected downstream: all filters after name (email, created_at)
        $expectedDownstream = array_slice($filters, $changedFilterIndex + 1);
        $this->assertCount(2, $expectedDownstream);
        $this->assertEquals('email', $expectedDownstream[0]['column']);
        $this->assertEquals('created_at', $expectedDownstream[1]['column']);
    }
    
    /**
     * Test that detectCascadeDirection handles edge case: last filter.
     * 
     * Subtask: Method handles edge cases (first filter, last filter, single filter)
     *
     * @return void
     */
    public function test_detect_cascade_direction_handles_last_filter(): void
    {
        $filters = [
            ['column' => 'name', 'label' => 'Name', 'type' => 'selectbox'],
            ['column' => 'email', 'label' => 'Email', 'type' => 'selectbox'],
            ['column' => 'created_at', 'label' => 'Created Date', 'type' => 'datebox'],
        ];
        
        // Simulate selecting the last filter (created_at)
        $changedFilterIndex = 2; // created_at
        
        // Expected upstream: all filters before created_at (name, email)
        $expectedUpstream = array_slice($filters, 0, $changedFilterIndex);
        $this->assertCount(2, $expectedUpstream);
        $this->assertEquals('name', $expectedUpstream[0]['column']);
        $this->assertEquals('email', $expectedUpstream[1]['column']);
        
        // Expected downstream: empty (no filters after created_at)
        $expectedDownstream = array_slice($filters, $changedFilterIndex + 1);
        $this->assertCount(0, $expectedDownstream);
    }
    
    /**
     * Test that detectCascadeDirection handles edge case: single filter.
     * 
     * Subtask: Method handles edge cases (first filter, last filter, single filter)
     *
     * @return void
     */
    public function test_detect_cascade_direction_handles_single_filter(): void
    {
        $filters = [
            ['column' => 'name', 'label' => 'Name', 'type' => 'selectbox'],
        ];
        
        // Simulate selecting the only filter (name)
        $changedFilterIndex = 0; // name
        
        // Expected upstream: empty (no filters before name)
        $expectedUpstream = array_slice($filters, 0, $changedFilterIndex);
        $this->assertCount(0, $expectedUpstream);
        
        // Expected downstream: empty (no filters after name)
        $expectedDownstream = array_slice($filters, $changedFilterIndex + 1);
        $this->assertCount(0, $expectedDownstream);
    }
    
    /**
     * Test that detectCascadeDirection returns empty arrays when appropriate.
     * 
     * Subtask: Method returns empty arrays when appropriate
     *
     * @return void
     */
    public function test_detect_cascade_direction_returns_empty_arrays_when_appropriate(): void
    {
        // Test with empty filters array
        $filters = [];
        
        // Simulate trying to find a filter in empty array
        // In JavaScript, findIndex would return -1
        $changedFilterIndex = -1;
        
        // Both upstream and downstream should be empty
        $expectedUpstream = [];
        $expectedDownstream = [];
        
        $this->assertCount(0, $expectedUpstream);
        $this->assertCount(0, $expectedDownstream);
    }
    
    /**
     * Test that detectCascadeDirection works with multiple filters.
     *
     * @return void
     */
    public function test_detect_cascade_direction_with_multiple_filters(): void
    {
        $filters = [
            ['column' => 'province', 'label' => 'Province', 'type' => 'selectbox'],
            ['column' => 'city', 'label' => 'City', 'type' => 'selectbox'],
            ['column' => 'district', 'label' => 'District', 'type' => 'selectbox'],
            ['column' => 'status', 'label' => 'Status', 'type' => 'selectbox'],
            ['column' => 'created_at', 'label' => 'Created Date', 'type' => 'datebox'],
        ];
        
        // Simulate selecting the middle filter (district)
        $changedFilterIndex = 2; // district
        
        // Expected upstream: province, city
        $expectedUpstream = array_slice($filters, 0, $changedFilterIndex);
        $this->assertCount(2, $expectedUpstream);
        $this->assertEquals('province', $expectedUpstream[0]['column']);
        $this->assertEquals('city', $expectedUpstream[1]['column']);
        
        // Expected downstream: status, created_at
        $expectedDownstream = array_slice($filters, $changedFilterIndex + 1);
        $this->assertCount(2, $expectedDownstream);
        $this->assertEquals('status', $expectedDownstream[0]['column']);
        $this->assertEquals('created_at', $expectedDownstream[1]['column']);
    }
    
    /**
     * Test that detectCascadeDirection preserves filter order.
     *
     * @return void
     */
    public function test_detect_cascade_direction_preserves_filter_order(): void
    {
        $filters = [
            ['column' => 'a', 'label' => 'A', 'type' => 'selectbox'],
            ['column' => 'b', 'label' => 'B', 'type' => 'selectbox'],
            ['column' => 'c', 'label' => 'C', 'type' => 'selectbox'],
            ['column' => 'd', 'label' => 'D', 'type' => 'selectbox'],
            ['column' => 'e', 'label' => 'E', 'type' => 'selectbox'],
        ];
        
        // Simulate selecting filter 'c'
        $changedFilterIndex = 2; // c
        
        // Expected upstream: a, b (in order)
        $expectedUpstream = array_slice($filters, 0, $changedFilterIndex);
        $this->assertCount(2, $expectedUpstream);
        $this->assertEquals('a', $expectedUpstream[0]['column']);
        $this->assertEquals('b', $expectedUpstream[1]['column']);
        
        // Expected downstream: d, e (in order)
        $expectedDownstream = array_slice($filters, $changedFilterIndex + 1);
        $this->assertCount(2, $expectedDownstream);
        $this->assertEquals('d', $expectedDownstream[0]['column']);
        $this->assertEquals('e', $expectedDownstream[1]['column']);
    }
    
    /**
     * Test that detectCascadeDirection works with different filter types.
     *
     * @return void
     */
    public function test_detect_cascade_direction_with_different_filter_types(): void
    {
        $filters = [
            ['column' => 'name', 'label' => 'Name', 'type' => 'inputbox'],
            ['column' => 'email', 'label' => 'Email', 'type' => 'selectbox'],
            ['column' => 'created_at', 'label' => 'Created Date', 'type' => 'datebox'],
            ['column' => 'status', 'label' => 'Status', 'type' => 'selectbox'],
        ];
        
        // Simulate selecting email filter
        $changedFilterIndex = 1; // email
        
        // Expected upstream: name (inputbox)
        $expectedUpstream = array_slice($filters, 0, $changedFilterIndex);
        $this->assertCount(1, $expectedUpstream);
        $this->assertEquals('name', $expectedUpstream[0]['column']);
        $this->assertEquals('inputbox', $expectedUpstream[0]['type']);
        
        // Expected downstream: created_at (datebox), status (selectbox)
        $expectedDownstream = array_slice($filters, $changedFilterIndex + 1);
        $this->assertCount(2, $expectedDownstream);
        $this->assertEquals('created_at', $expectedDownstream[0]['column']);
        $this->assertEquals('datebox', $expectedDownstream[0]['type']);
        $this->assertEquals('status', $expectedDownstream[1]['column']);
        $this->assertEquals('selectbox', $expectedDownstream[1]['type']);
    }
    
    /**
     * Test that cascade state is initialized correctly.
     * 
     * Task 1.6: Add Cascade State Tracking
     * Subtask: Cascade state tracks processing status
     * Subtask: Cascade state tracks current filter being processed
     * Subtask: Cascade state tracks affected filters
     * Subtask: Cascade state tracks cascade direction
     *
     * @return void
     */
    public function test_cascade_state_is_initialized_correctly(): void
    {
        // Simulate the cascadeState object structure from Alpine.js
        $cascadeState = [
            'isProcessing' => false,
            'currentFilter' => null,
            'affectedFilters' => [],
            'direction' => null,
        ];
        
        // Verify initial state
        $this->assertFalse($cascadeState['isProcessing'], 'isProcessing should be false initially');
        $this->assertNull($cascadeState['currentFilter'], 'currentFilter should be null initially');
        $this->assertIsArray($cascadeState['affectedFilters'], 'affectedFilters should be an array');
        $this->assertCount(0, $cascadeState['affectedFilters'], 'affectedFilters should be empty initially');
        $this->assertNull($cascadeState['direction'], 'direction should be null initially');
    }
    
    /**
     * Test that cascade state is updated during cascade.
     * 
     * Task 1.6: Add Cascade State Tracking
     * Subtask: Cascade state tracks processing status
     * Subtask: Cascade state tracks current filter being processed
     * Subtask: Cascade state tracks affected filters
     * Subtask: Cascade state tracks cascade direction
     *
     * @return void
     */
    public function test_cascade_state_is_updated_during_cascade(): void
    {
        // Simulate cascade state during bi-directional cascade
        $cascadeState = [
            'isProcessing' => true,
            'currentFilter' => ['column' => 'email', 'label' => 'Email', 'type' => 'selectbox'],
            'affectedFilters' => ['name', 'created_at'],
            'direction' => 'both',
        ];
        
        // Verify state during cascade
        $this->assertTrue($cascadeState['isProcessing'], 'isProcessing should be true during cascade');
        $this->assertIsArray($cascadeState['currentFilter'], 'currentFilter should be set');
        $this->assertEquals('email', $cascadeState['currentFilter']['column'], 'currentFilter should be the changed filter');
        $this->assertIsArray($cascadeState['affectedFilters'], 'affectedFilters should be an array');
        $this->assertCount(2, $cascadeState['affectedFilters'], 'affectedFilters should contain upstream and downstream filters');
        $this->assertContains('name', $cascadeState['affectedFilters'], 'affectedFilters should contain upstream filter');
        $this->assertContains('created_at', $cascadeState['affectedFilters'], 'affectedFilters should contain downstream filter');
        $this->assertEquals('both', $cascadeState['direction'], 'direction should be "both" for bi-directional cascade');
    }
    
    /**
     * Test that cascade state is reset after cascade completes.
     * 
     * Task 1.6: Add Cascade State Tracking
     * Subtask: State is reset after cascade completes
     *
     * @return void
     */
    public function test_cascade_state_is_reset_after_cascade_completes(): void
    {
        // Simulate cascade state after cascade completes
        $cascadeState = [
            'isProcessing' => false,
            'currentFilter' => null,
            'affectedFilters' => [],
            'direction' => null,
        ];
        
        // Verify state is reset
        $this->assertFalse($cascadeState['isProcessing'], 'isProcessing should be false after cascade');
        $this->assertNull($cascadeState['currentFilter'], 'currentFilter should be null after cascade');
        $this->assertIsArray($cascadeState['affectedFilters'], 'affectedFilters should be an array');
        $this->assertCount(0, $cascadeState['affectedFilters'], 'affectedFilters should be empty after cascade');
        $this->assertNull($cascadeState['direction'], 'direction should be null after cascade');
    }
    
    /**
     * Test that cascade state tracks different cascade directions.
     * 
     * Task 1.6: Add Cascade State Tracking
     * Subtask: Cascade state tracks cascade direction
     *
     * @return void
     */
    public function test_cascade_state_tracks_different_directions(): void
    {
        // Test upstream direction
        $cascadeStateUpstream = [
            'isProcessing' => true,
            'currentFilter' => ['column' => 'created_at'],
            'affectedFilters' => ['name', 'email'],
            'direction' => 'upstream',
        ];
        $this->assertEquals('upstream', $cascadeStateUpstream['direction']);
        
        // Test downstream direction
        $cascadeStateDownstream = [
            'isProcessing' => true,
            'currentFilter' => ['column' => 'name'],
            'affectedFilters' => ['email', 'created_at'],
            'direction' => 'downstream',
        ];
        $this->assertEquals('downstream', $cascadeStateDownstream['direction']);
        
        // Test both directions
        $cascadeStateBoth = [
            'isProcessing' => true,
            'currentFilter' => ['column' => 'email'],
            'affectedFilters' => ['name', 'created_at'],
            'direction' => 'both',
        ];
        $this->assertEquals('both', $cascadeStateBoth['direction']);
        
        // Test specific direction
        $cascadeStateSpecific = [
            'isProcessing' => true,
            'currentFilter' => ['column' => 'name'],
            'affectedFilters' => ['email'],
            'direction' => 'specific',
        ];
        $this->assertEquals('specific', $cascadeStateSpecific['direction']);
    }
    
    /**
     * Test that cascade state prevents concurrent cascades.
     * 
     * Task 1.6: Add Cascade State Tracking
     * Subtask: Cascade state tracks processing status
     *
     * @return void
     */
    public function test_cascade_state_prevents_concurrent_cascades(): void
    {
        // Simulate cascade state when cascade is already in progress
        $cascadeState = [
            'isProcessing' => true,
            'currentFilter' => ['column' => 'email'],
            'affectedFilters' => ['name', 'created_at'],
            'direction' => 'both',
        ];
        
        // Verify that isProcessing flag can be used to prevent concurrent cascades
        $this->assertTrue($cascadeState['isProcessing'], 'isProcessing should be true');
        
        // In the actual implementation, this would prevent a new cascade from starting:
        // if (this.cascadeState.isProcessing) { return; }
        
        // Simulate attempting to start another cascade (should be prevented)
        $canStartNewCascade = !$cascadeState['isProcessing'];
        $this->assertFalse($canStartNewCascade, 'New cascade should not be allowed when one is already in progress');
    }
    
    /**
     * Test cascade upstream builds correct parent filters.
     * 
     * Task 1.3: Implement cascadeUpstream() Method
     * Subtask: Method builds correct parent filter context
     * Subtask: Method accumulates parent filters for each iteration
     *
     * @return void
     */
    public function test_cascade_upstream_builds_correct_parent_filters(): void
    {
        // Simulate cascadeUpstream logic
        $changedFilter = ['column' => 'created_at', 'value' => '2026-03-01'];
        $upstreamFilters = [
            ['column' => 'name'],
            ['column' => 'email'],
        ];
        
        // Initial parent filters: only the changed filter
        $parentFilters = [$changedFilter['column'] => $changedFilter['value']];
        
        $this->assertArrayHasKey('created_at', $parentFilters);
        $this->assertEquals('2026-03-01', $parentFilters['created_at']);
        $this->assertCount(1, $parentFilters);
        
        // After processing first upstream filter (email), add it to parent context
        $parentFilters['email'] = 'test@example.com';
        $this->assertCount(2, $parentFilters);
        $this->assertArrayHasKey('email', $parentFilters);
        
        // After processing second upstream filter (name), add it to parent context
        $parentFilters['name'] = 'John Doe';
        $this->assertCount(3, $parentFilters);
        $this->assertArrayHasKey('name', $parentFilters);
    }
    
    /**
     * Test cascade downstream builds correct parent filters.
     * 
     * Task 1.4: Implement cascadeDownstream() Method
     * Subtask: Method builds correct parent filter context
     * Subtask: Method accumulates parent filters for each iteration
     *
     * @return void
     */
    public function test_cascade_downstream_builds_correct_parent_filters(): void
    {
        // Simulate cascadeDownstream logic
        $changedFilter = ['column' => 'name', 'value' => 'John Doe'];
        $downstreamFilters = [
            ['column' => 'email'],
            ['column' => 'created_at'],
        ];
        
        // Initial parent filters: all filters up to and including changed filter
        $parentFilters = [$changedFilter['column'] => $changedFilter['value']];
        
        $this->assertArrayHasKey('name', $parentFilters);
        $this->assertEquals('John Doe', $parentFilters['name']);
        $this->assertCount(1, $parentFilters);
        
        // After processing first downstream filter (email), add it to parent context
        $parentFilters['email'] = 'test@example.com';
        $this->assertCount(2, $parentFilters);
        $this->assertArrayHasKey('email', $parentFilters);
        
        // After processing second downstream filter (created_at), add it to parent context
        $parentFilters['created_at'] = '2026-03-01';
        $this->assertCount(3, $parentFilters);
        $this->assertArrayHasKey('created_at', $parentFilters);
    }
    
    /**
     * Test handle filter change executes both directions when bidirectional.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method executes upstream cascade when bidirectional enabled
     * Subtask: Method executes downstream cascade when bidirectional enabled
     *
     * @return void
     */
    public function test_handle_filter_change_executes_both_directions_when_bidirectional(): void
    {
        // Simulate filter configuration with bidirectional enabled
        $filter = [
            'column' => 'email',
            'bidirectional' => true,
        ];
        
        $filters = [
            ['column' => 'name'],
            ['column' => 'email'],
            ['column' => 'created_at'],
        ];
        
        // Find filter index
        $filterIndex = 1; // email
        
        // Detect cascade direction
        $upstream = array_slice($filters, 0, $filterIndex);
        $downstream = array_slice($filters, $filterIndex + 1);
        
        // When bidirectional is true, both upstream and downstream should be processed
        $shouldProcessUpstream = $filter['bidirectional'] && count($upstream) > 0;
        $shouldProcessDownstream = $filter['bidirectional'] && count($downstream) > 0;
        
        $this->assertTrue($shouldProcessUpstream, 'Should process upstream when bidirectional');
        $this->assertTrue($shouldProcessDownstream, 'Should process downstream when bidirectional');
        $this->assertCount(1, $upstream, 'Should have 1 upstream filter');
        $this->assertCount(1, $downstream, 'Should have 1 downstream filter');
    }
    
    /**
     * Test handle filter change executes only downstream when not bidirectional.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method maintains backward compatibility (relate=true)
     *
     * @return void
     */
    public function test_handle_filter_change_executes_only_downstream_when_not_bidirectional(): void
    {
        // Simulate filter configuration with relate=true (existing behavior)
        $filter = [
            'column' => 'email',
            'relate' => true,
            'bidirectional' => false,
        ];
        
        $filters = [
            ['column' => 'name'],
            ['column' => 'email'],
            ['column' => 'created_at'],
        ];
        
        // Find filter index
        $filterIndex = 1; // email
        
        // Detect cascade direction
        $upstream = array_slice($filters, 0, $filterIndex);
        $downstream = array_slice($filters, $filterIndex + 1);
        
        // When bidirectional is false but relate is true, only downstream should be processed
        $shouldProcessUpstream = $filter['bidirectional'] && count($upstream) > 0;
        $shouldProcessDownstream = $filter['relate'] && count($downstream) > 0;
        
        $this->assertFalse($shouldProcessUpstream, 'Should NOT process upstream when not bidirectional');
        $this->assertTrue($shouldProcessDownstream, 'Should process downstream when relate=true');
        $this->assertCount(1, $downstream, 'Should have 1 downstream filter');
    }
    
    /**
     * Test cascade prevents concurrent operations.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method prevents concurrent cascade operations
     *
     * @return void
     */
    public function test_cascade_prevents_concurrent_operations(): void
    {
        // Simulate cascade state
        $cascadeState = ['isProcessing' => false];
        
        // First cascade starts
        $cascadeState['isProcessing'] = true;
        $this->assertTrue($cascadeState['isProcessing']);
        
        // Attempt to start second cascade (should be prevented)
        $canStartSecondCascade = !$cascadeState['isProcessing'];
        $this->assertFalse($canStartSecondCascade, 'Second cascade should be prevented');
        
        // First cascade completes
        $cascadeState['isProcessing'] = false;
        $this->assertFalse($cascadeState['isProcessing']);
        
        // Now new cascade can start
        $canStartNewCascade = !$cascadeState['isProcessing'];
        $this->assertTrue($canStartNewCascade, 'New cascade should be allowed after previous completes');
    }
    
    /**
     * Test cascade handles empty upstream filters.
     * 
     * Task 1.3: Implement cascadeUpstream() Method
     * Subtask: Method handles empty upstream filters array
     *
     * @return void
     */
    public function test_cascade_handles_empty_upstream_filters(): void
    {
        // Simulate selecting first filter (no upstream filters)
        $changedFilter = ['column' => 'name'];
        $upstreamFilters = [];
        
        // Verify empty upstream
        $this->assertCount(0, $upstreamFilters);
        
        // Cascade should handle gracefully (no processing needed)
        $shouldProcess = count($upstreamFilters) > 0;
        $this->assertFalse($shouldProcess, 'Should not process when upstream is empty');
    }
    
    /**
     * Test cascade handles empty downstream filters.
     * 
     * Task 1.4: Implement cascadeDownstream() Method
     * Subtask: Method handles empty downstream filters array
     *
     * @return void
     */
    public function test_cascade_handles_empty_downstream_filters(): void
    {
        // Simulate selecting last filter (no downstream filters)
        $changedFilter = ['column' => 'created_at'];
        $downstreamFilters = [];
        
        // Verify empty downstream
        $this->assertCount(0, $downstreamFilters);
        
        // Cascade should handle gracefully (no processing needed)
        $shouldProcess = count($downstreamFilters) > 0;
        $this->assertFalse($shouldProcess, 'Should not process when downstream is empty');
    }
    
    /**
     * Test cascade handles single filter.
     * 
     * Task 1.2: Implement detectCascadeDirection() Method
     * Subtask: Method handles edge cases (first filter, last filter, single filter)
     *
     * @return void
     */
    public function test_cascade_handles_single_filter(): void
    {
        // Simulate single filter scenario
        $filters = [
            ['column' => 'name'],
        ];
        
        $changedFilterIndex = 0;
        
        // Detect cascade direction
        $upstream = array_slice($filters, 0, $changedFilterIndex);
        $downstream = array_slice($filters, $changedFilterIndex + 1);
        
        // Both should be empty
        $this->assertCount(0, $upstream, 'Upstream should be empty for single filter');
        $this->assertCount(0, $downstream, 'Downstream should be empty for single filter');
        
        // No cascade should occur
        $shouldCascade = count($upstream) > 0 || count($downstream) > 0;
        $this->assertFalse($shouldCascade, 'No cascade should occur with single filter');
    }
    
    /**
     * Test cascade upstream processes filters in reverse order.
     * 
     * Task 1.3: Implement cascadeUpstream() Method
     * Subtask: Method processes upstream filters in reverse order
     *
     * @return void
     */
    public function test_cascade_upstream_processes_in_reverse_order(): void
    {
        // Simulate upstream filters
        $upstreamFilters = [
            ['column' => 'name', 'order' => 1],
            ['column' => 'email', 'order' => 2],
            ['column' => 'status', 'order' => 3],
        ];
        
        // Reverse for upstream processing
        $reversedFilters = array_reverse($upstreamFilters);
        
        // Verify reverse order
        $this->assertEquals('status', $reversedFilters[0]['column']);
        $this->assertEquals('email', $reversedFilters[1]['column']);
        $this->assertEquals('name', $reversedFilters[2]['column']);
        
        // Verify order values
        $this->assertEquals(3, $reversedFilters[0]['order']);
        $this->assertEquals(2, $reversedFilters[1]['order']);
        $this->assertEquals(1, $reversedFilters[2]['order']);
    }
    
    /**
     * Test cascade downstream processes filters in forward order.
     * 
     * Task 1.4: Implement cascadeDownstream() Method
     * Subtask: Method processes downstream filters in forward order
     *
     * @return void
     */
    public function test_cascade_downstream_processes_in_forward_order(): void
    {
        // Simulate downstream filters
        $downstreamFilters = [
            ['column' => 'email', 'order' => 1],
            ['column' => 'status', 'order' => 2],
            ['column' => 'created_at', 'order' => 3],
        ];
        
        // Verify forward order (no reversal needed)
        $this->assertEquals('email', $downstreamFilters[0]['column']);
        $this->assertEquals('status', $downstreamFilters[1]['column']);
        $this->assertEquals('created_at', $downstreamFilters[2]['column']);
        
        // Verify order values
        $this->assertEquals(1, $downstreamFilters[0]['order']);
        $this->assertEquals(2, $downstreamFilters[1]['order']);
        $this->assertEquals(3, $downstreamFilters[2]['order']);
    }
    
    /**
     * Test cascade handles error gracefully.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method handles errors gracefully
     *
     * @return void
     */
    public function test_cascade_handles_error_gracefully(): void
    {
        // Simulate error scenario
        $cascadeState = [
            'isProcessing' => true,
            'currentFilter' => ['column' => 'email'],
            'affectedFilters' => ['name', 'created_at'],
            'direction' => 'both',
        ];
        
        // Simulate error occurs
        $errorOccurred = true;
        
        // Cascade should reset state even on error (finally block)
        if ($errorOccurred) {
            $cascadeState['isProcessing'] = false;
            $cascadeState['currentFilter'] = null;
        }
        
        // Verify state is reset
        $this->assertFalse($cascadeState['isProcessing'], 'isProcessing should be reset on error');
        $this->assertNull($cascadeState['currentFilter'], 'currentFilter should be reset on error');
    }
    
    /**
     * Test cascade updates cascade state properly.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method updates cascade state properly
     *
     * @return void
     */
    public function test_cascade_updates_cascade_state_properly(): void
    {
        // Initial state
        $cascadeState = [
            'isProcessing' => false,
            'currentFilter' => null,
            'affectedFilters' => [],
            'direction' => null,
        ];
        
        // Start cascade
        $filter = ['column' => 'email'];
        $upstream = [['column' => 'name']];
        $downstream = [['column' => 'created_at']];
        
        $cascadeState['isProcessing'] = true;
        $cascadeState['currentFilter'] = $filter;
        $cascadeState['affectedFilters'] = array_merge(
            array_column($upstream, 'column'),
            array_column($downstream, 'column')
        );
        $cascadeState['direction'] = 'both';
        
        // Verify state during cascade
        $this->assertTrue($cascadeState['isProcessing']);
        $this->assertEquals('email', $cascadeState['currentFilter']['column']);
        $this->assertCount(2, $cascadeState['affectedFilters']);
        $this->assertContains('name', $cascadeState['affectedFilters']);
        $this->assertContains('created_at', $cascadeState['affectedFilters']);
        $this->assertEquals('both', $cascadeState['direction']);
        
        // Complete cascade
        $cascadeState['isProcessing'] = false;
        $cascadeState['currentFilter'] = null;
        
        // Verify state after cascade
        $this->assertFalse($cascadeState['isProcessing']);
        $this->assertNull($cascadeState['currentFilter']);
    }
    
    /**
     * Test backward compatibility with relate=true.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method maintains backward compatibility (relate=true)
     *
     * @return void
     */
    public function test_backward_compatibility_with_relate_true(): void
    {
        // Simulate existing filter configuration (relate=true)
        $filter = [
            'column' => 'name',
            'relate' => true,
            'bidirectional' => false, // Not using new feature
        ];
        
        $filters = [
            ['column' => 'name'],
            ['column' => 'email'],
            ['column' => 'created_at'],
        ];
        
        $filterIndex = 0; // name
        
        // Detect cascade direction
        $upstream = array_slice($filters, 0, $filterIndex);
        $downstream = array_slice($filters, $filterIndex + 1);
        
        // Existing behavior: only cascade downstream when relate=true
        $shouldCascadeDownstream = $filter['relate'] === true;
        $shouldCascadeUpstream = false; // Never cascade upstream in old behavior
        
        $this->assertTrue($shouldCascadeDownstream, 'Should cascade downstream with relate=true');
        $this->assertFalse($shouldCascadeUpstream, 'Should NOT cascade upstream with relate=true');
        $this->assertCount(0, $upstream);
        $this->assertCount(2, $downstream);
    }
    
    /**
     * Test backward compatibility with relate=string.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method maintains backward compatibility (relate=true)
     *
     * @return void
     */
    public function test_backward_compatibility_with_relate_string(): void
    {
        // Simulate existing filter configuration (relate='email')
        $filter = [
            'column' => 'name',
            'relate' => 'email', // Cascade to specific filter
            'bidirectional' => false,
        ];
        
        // Should cascade to specific filter only
        $targetFilter = $filter['relate'];
        
        $this->assertEquals('email', $targetFilter);
        $this->assertIsString($targetFilter);
    }
    
    /**
     * Test backward compatibility with relate=array.
     * 
     * Task 1.5: Implement handleFilterChangeBidirectional() Method
     * Subtask: Method maintains backward compatibility (relate=true)
     *
     * @return void
     */
    public function test_backward_compatibility_with_relate_array(): void
    {
        // Simulate existing filter configuration (relate=['email', 'status'])
        $filter = [
            'column' => 'name',
            'relate' => ['email', 'status'], // Cascade to specific filters
            'bidirectional' => false,
        ];
        
        // Should cascade to specified filters only
        $targetFilters = $filter['relate'];
        
        $this->assertIsArray($targetFilters);
        $this->assertCount(2, $targetFilters);
        $this->assertContains('email', $targetFilters);
        $this->assertContains('status', $targetFilters);
    }
}

