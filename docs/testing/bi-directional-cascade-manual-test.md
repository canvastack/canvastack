# Manual Testing Guide: detectCascadeDirection() Method

## Overview

This guide provides instructions for manually testing the `detectCascadeDirection()` method implementation in the filter modal.

## Test Location

**URL**: `http://localhost:8000/test/filter-modal`

## What Was Implemented

The `detectCascadeDirection()` method has been added to the filter modal Alpine.js component. This method:

1. Identifies which filters are "upstream" (before) the changed filter
2. Identifies which filters are "downstream" (after) the changed filter
3. Handles edge cases (first filter, last filter, single filter)
4. Returns empty arrays when appropriate

## Implementation Details

**File**: `packages/canvastack/canvastack/resources/views/components/table/filter-modal.blade.php`

**Method Location**: Inside the `filterModal()` Alpine.js function, before `handleFilterChange()`

**Method Signature**:
```javascript
detectCascadeDirection(changedFilter) {
    // Returns: { upstream: [], downstream: [] }
}
```

## Manual Testing Steps

### Test 1: Middle Filter Selection

1. Open browser console (F12)
2. Navigate to `http://localhost:8000/test/filter-modal`
3. Open the filter modal
4. Select the middle filter (e.g., "Email")
5. Check console output for:
   ```
   Cascade direction detected: {
       changedFilter: "email",
       filterIndex: 1,
       totalFilters: 3,
       upstreamCount: 1,
       downstreamCount: 1,
       upstream: ["name"],
       downstream: ["created_at"]
   }
   ```

**Expected Result**: 
- Upstream should contain filters before "email" (e.g., "name")
- Downstream should contain filters after "email" (e.g., "created_at")

### Test 2: First Filter Selection

1. Open the filter modal
2. Select the first filter (e.g., "Name")
3. Check console output for:
   ```
   Cascade direction detected: {
       changedFilter: "name",
       filterIndex: 0,
       totalFilters: 3,
       upstreamCount: 0,
       downstreamCount: 2,
       upstream: [],
       downstream: ["email", "created_at"]
   }
   ```

**Expected Result**:
- Upstream should be empty (no filters before first filter)
- Downstream should contain all filters after "name"

### Test 3: Last Filter Selection

1. Open the filter modal
2. Select the last filter (e.g., "Created Date")
3. Check console output for:
   ```
   Cascade direction detected: {
       changedFilter: "created_at",
       filterIndex: 2,
       totalFilters: 3,
       upstreamCount: 2,
       downstreamCount: 0,
       upstream: ["name", "email"],
       downstream: []
   }
   ```

**Expected Result**:
- Upstream should contain all filters before "created_at"
- Downstream should be empty (no filters after last filter)

### Test 4: Console Verification

Open browser console and manually call the method:

```javascript
// Get the Alpine.js component
const filterModal = Alpine.$data(document.querySelector('[x-data="filterModal()"]'));

// Test with middle filter
const emailFilter = filterModal.filters.find(f => f.column === 'email');
const result = filterModal.detectCascadeDirection(emailFilter);

console.log('Upstream:', result.upstream.map(f => f.column));
console.log('Downstream:', result.downstream.map(f => f.column));
```

## Automated Tests

Unit tests have been created to verify the logic:

**File**: `packages/canvastack/canvastack/tests/Unit/Components/Table/BiDirectionalCascadeTest.php`

**Run tests**:
```bash
cd packages/canvastack/canvastack
./vendor/bin/phpunit tests/Unit/Components/Table/BiDirectionalCascadeTest.php
```

**Expected Output**:
```
PHPUnit 11.5.55 by Sebastian Bergmann and contributors.

........                                                            8 / 8 (100%)

Time: 00:01.171, Memory: 20.00 MB

OK (8 tests, 36 assertions)
```

## Test Coverage

The following test cases are covered:

1. ✅ Method correctly identifies upstream filters (before changed filter)
2. ✅ Method correctly identifies downstream filters (after changed filter)
3. ✅ Method handles edge case: first filter
4. ✅ Method handles edge case: last filter
5. ✅ Method handles edge case: single filter
6. ✅ Method returns empty arrays when appropriate
7. ✅ Method works with multiple filters
8. ✅ Method preserves filter order
9. ✅ Method works with different filter types

## Next Steps

This method is the foundation for bi-directional cascade. The next tasks will implement:

1. **Task 1.3**: `cascadeUpstream()` - Update filters before the changed filter
2. **Task 1.4**: `cascadeDownstream()` - Update filters after the changed filter
3. **Task 1.5**: `handleFilterChangeBidirectional()` - Main orchestration method

## Troubleshooting

### Console shows "Filter not found" warning

**Cause**: The filter object passed to `detectCascadeDirection()` doesn't exist in the filters array.

**Solution**: Verify that the filter object is from the same filters array.

### Upstream/Downstream counts are incorrect

**Cause**: Filter order might be different than expected.

**Solution**: Check the filters array order in the console:
```javascript
console.log(filterModal.filters.map(f => f.column));
```

### Method not found error

**Cause**: The method might not be loaded yet.

**Solution**: Ensure the page has fully loaded and Alpine.js is initialized.

---

**Last Updated**: 2026-03-03  
**Version**: 1.0.0  
**Status**: Complete  
**Task**: Task 1.2 - Implement detectCascadeDirection() Method
