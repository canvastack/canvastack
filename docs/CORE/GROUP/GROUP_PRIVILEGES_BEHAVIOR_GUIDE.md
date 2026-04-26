# Group Privileges Behavior Guide

**Document Version:** 1.0  
**Last Updated:** 2026-04-08  
**Author:** Development Team

## Table of Contents

1. [Overview](#overview)
2. [Architecture](#architecture)
3. [Module Privileges Tab](#module-privileges-tab)
4. [Mapping Page Privileges Tab](#mapping-page-privileges-tab)
5. [Complete Scenario Matrix](#complete-scenario-matrix)
6. [Code Flow](#code-flow)
7. [Database Schema](#database-schema)
8. [Troubleshooting](#troubleshooting)

---

## Overview

The Group edit form provides two tabs for managing access control:

1. **Module Privileges Tab**: Feature-level access control (CRUD permissions per module)
2. **Mapping Page Privileges Tab**: Data-level access control (field-value filters per module)

Both tabs support full CRUD operations including the critical "clear all" functionality
where users can remove all privileges by unchecking all checkboxes or clearing all selections.

### Key Design Principles

- **Always Process**: Both systems always process data, even when empty
- **Consistent Behavior**: Both tabs handle "clear all" scenarios gracefully
- **Different Strategies**: 
  - Module Privileges: UPDATE to NULL (preserve records)
  - Mapping Privileges: DELETE (remove records)

---

## Architecture

### Request Flow

```
User Form Submission
        ↓
GroupController::update()
        ↓
set_data_before_insert()
        ├→ privileges_before_insert()  (prepares module privileges)
        └→ mapping_before_insert()     (processes mapping privileges)
        ↓
set_data_after_insert()
        └→ privileges_after_insert()   (saves module privileges)
```

### Data Processing Pipeline


**Module Privileges:**
```
Form Data → privileges_before_insert() → $this->roles → privileges_after_insert() → Database
```

**Mapping Page Privileges:**
```
Form Data → mapping_before_insert() → $roles → insert_process() → Database
```

---

## Module Privileges Tab

### Purpose

Controls which features (modules) a user group can access and what operations they can perform.

### Privilege Values

- **8** = Read/View (index, show)
- **4** = Create/Insert (create, insert)
- **2** = Update/Edit (edit, update)
- **1** = Delete/Destroy (destroy, delete)

### Privilege Types

1. **Admin Privilege**: Backend/admin operations
2. **Index Privilege**: Frontend/listing operations

### Form Structure

```html
<input type="checkbox" name="modules[admin_privilege][admin.content.articles][8]" value="12">
<input type="checkbox" name="modules[admin_privilege][admin.content.articles][4]" value="12">
<input type="checkbox" name="modules[index_privilege][admin.content.articles][8]" value="12">
```

### Request Data Format

```php
$request['modules'] = [
    'admin_privilege' => [
        'admin.content.articles' => [
            '8' => '12',  // Read permission for module ID 12
            '4' => '12'   // Create permission for module ID 12
        ]
    ],
    'index_privilege' => [
        'admin.content.articles' => [
            '8' => '12'   // Read permission for module ID 12
        ]
    ]
];
```


### Database Storage

**Table:** `base_group_privilege`

```sql
CREATE TABLE base_group_privilege (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    module_id INT NOT NULL,
    admin_privilege VARCHAR(50) NULL,  -- e.g., "8:4:2:1" or NULL
    index_privilege VARCHAR(50) NULL,  -- e.g., "8" or NULL
    UNIQUE KEY (group_id, module_id)
);
```

**Storage Format:**
- Privileges stored as colon-separated values: `"8:4:2:1"`
- NULL means no privileges for that type
- Record is preserved even when all privileges are NULL

### Processing Methods

#### privileges_before_insert()

**Purpose:** Prepare privilege data for database operations

**Input:** HTTP Request with form data  
**Output:** Stores prepared data in `$this->roles`

**Logic:**
1. Check if `$request['modules']` exists
2. If YES: Build array of privilege records
3. If NO: Set "setnull" marker to clear all privileges

**Output Examples:**

Normal case (modules selected):
```php
$this->roles = [
    [
        'group_id' => 1,
        'module_id' => 12,
        'admin_privilege' => '8:4',
        'index_privilege' => '8'
    ]
];
```

Clear all case (no modules selected):
```php
$this->roles = [
    'setnull' => [
        'group_id' => 1
    ]
];
```


#### privileges_after_insert()

**Purpose:** Execute database operations (INSERT/UPDATE)

**Input:** Data from `$this->roles`  
**Output:** Database changes

**Strategy:** "Clear First, Then Apply"

**Logic:**

1. **If "setnull" marker present:**
   ```sql
   UPDATE base_group_privilege 
   SET admin_privilege = NULL, index_privilege = NULL 
   WHERE group_id = ?
   ```

2. **If normal data:**
   - Step 1: Clear all privileges for group (same UPDATE as above)
   - Step 2: For each module in data:
     - Check if record exists (group_id + module_id)
     - If exists: UPDATE with new values
     - If not exists: INSERT new record
     - If both privileges NULL: SKIP (already cleared)

**Why "Clear First"?**
- Ensures removed modules are set to NULL
- Prevents orphaned privileges
- Simplifies logic (no need to track deletions)

---

## Mapping Page Privileges Tab

### Purpose

Controls which data records a user group can access based on field values (row-level security).

### Use Cases

- Restrict users to see only their department's data
- Filter records by status, region, category, etc.
- Multi-tenant data isolation

### Form Structure

```html
<select name="rolePages[module][admin.content.articles]">
    <option value="12">Articles Module</option>
</select>
<select name="rolePages[field_name][admin.content.articles][users][]">
    <option value="department">Department</option>
    <option value="status">Status</option>
</select>
<select name="rolePages[field_value][admin.content.articles][users][department][]" multiple>
    <option value="sales">Sales</option>
    <option value="marketing">Marketing</option>
</select>
```


### Request Data Format

```php
$request['rolePages'] = [
    'module' => [
        'admin.content.articles' => 12  // route_path => module_id
    ],
    'field_name' => [
        'admin.content.articles' => [
            'users' => ['department', 'status']  // table => [field_names]
        ]
    ],
    'field_value' => [
        'admin.content.articles' => [
            'users' => [
                'department' => ['sales', 'marketing'],  // field => [values]
                'status' => ['active']
            ]
        ]
    ]
];
```

### Database Storage

**Table:** `base_page_privilege`

```sql
CREATE TABLE base_page_privilege (
    id INT PRIMARY KEY AUTO_INCREMENT,
    group_id INT NOT NULL,
    module_id INT NOT NULL,
    target_table VARCHAR(100) NOT NULL,
    target_field_name VARCHAR(100) NOT NULL,
    target_field_values TEXT NULL,  -- e.g., "sales::marketing" or NULL
    KEY (group_id, module_id)
);
```

**Storage Format:**
- Multiple values stored as double-colon separated: `"sales::marketing"`
- NULL means no filter (access all values)
- Records are DELETED when mapping is removed (unlike module privileges)

### Processing Methods

#### mapping_before_insert()

**Purpose:** Process and save mapping privileges immediately

**Input:** HTTP Request with form data  
**Output:** Direct database changes via `insert_process()`

**Logic:**
1. Check if `$request['rolePages']` exists
2. Build `$roles` array from form data
3. ALWAYS call `insert_process()`, even if `$roles` is empty
4. Empty `$roles` triggers DELETE of all existing mappings


**CRITICAL BUGFIX (2026-04-08):**
Previously had early returns that prevented `insert_process()` from being called when
data was empty, so DELETE logic never ran. This caused mappings to persist even when
users cleared all selections.

**Fixed by:** Removing all early returns and always calling `insert_process()`

**Roles Array Format:**

Normal case (mappings selected):
```php
$roles = [
    'admin.content.articles' => [
        'users' => [
            'department' => [
                'group_id' => 1,
                'module_id' => 12,
                'target_table' => 'users',
                'target_field_name' => 'department',
                'target_field_values' => 'sales::marketing'
            ]
        ]
    ]
];
```

Clear all case (no mappings selected):
```php
$roles = [];  // Empty array triggers DELETE
```

#### insert_process()

**Purpose:** Execute database operations (INSERT/UPDATE/DELETE)

**Input:** `$roles` array from `mapping_before_insert()`  
**Output:** Database changes

**Strategy:** "Compare and Sync"

**Logic:**

1. **Load existing data:**
   - Query all mappings for this group
   - Build comparison arrays

2. **Determine operations:**
   - **INSERT**: Mapping in `$roles` but not in database
   - **UPDATE**: Mapping in both but values differ
   - **DELETE**: Mapping in database but not in `$roles`

3. **Execute operations:**
   ```sql
   -- INSERT
   INSERT INTO base_page_privilege (group_id, module_id, target_table, target_field_name, target_field_values)
   VALUES (?, ?, ?, ?, ?)
   
   -- UPDATE
   UPDATE base_page_privilege 
   SET target_field_values = ? 
   WHERE id = ?
   
   -- DELETE
   DELETE FROM base_page_privilege 
   WHERE id = ?
   ```


**Why DELETE instead of NULL?**
- Mapping privileges are optional filters
- No mapping = no filter = access all data
- Cleaner to remove record than keep NULL entries
- Different from module privileges which are core access control

---

## Complete Scenario Matrix

### Scenario Definitions

| # | Initial State | User Action | Expected Result |
|---|---------------|-------------|-----------------|
| 1 | No data in both tabs | Add module privileges only | Module privileges inserted, no mapping |
| 2 | No data in both tabs | Add mapping only | Mapping inserted, no module privileges |
| 3 | No data in both tabs | Add both | Both inserted |
| 4 | Has both tabs | Clear both | Module privileges set to NULL, mappings deleted |
| 5 | Has both tabs | Clear module only | Module privileges set to NULL, mappings kept |
| 6 | Has both tabs | Clear mapping only | Module privileges kept, mappings deleted |
| 7 | Has both tabs | Update module only | Module privileges updated, mappings kept |
| 8 | Has both tabs | Update mapping only | Module privileges kept, mappings updated |
| 9 | Has both tabs | Update both | Both updated |
| 10 | Has module only | Add mapping | Module kept, mapping inserted |
| 11 | Has module only | Clear module | Module privileges set to NULL |
| 12 | Has module only | Update module | Module privileges updated |
| 13 | Has mapping only | Add module | Mapping kept, module inserted |
| 14 | Has mapping only | Clear mapping | Mappings deleted |
| 15 | Has mapping only | Update mapping | Mappings updated |

### Detailed Scenario Walkthroughs

#### Scenario 1: Add Module Privileges Only

**Initial State:**
```sql
-- No records in base_group_privilege for group_id = 1
-- No records in base_page_privilege for group_id = 1
```

**User Action:**
- Check "Read" and "Create" for "Articles" module
- Submit form

**Request Data:**
```php
[
    'modules' => [
        'admin_privilege' => [
            'admin.content.articles' => ['8' => '12', '4' => '12']
        ]
    ]
    // NO 'rolePages' key
]
```


**Processing:**

1. `privileges_before_insert()`:
   ```php
   $this->roles = [
       ['group_id' => 1, 'module_id' => 12, 'admin_privilege' => '8:4', 'index_privilege' => null]
   ];
   ```

2. `mapping_before_insert()`:
   - No 'rolePages' key found
   - Builds empty `$roles = []`
   - Calls `insert_process([])` which does nothing (no existing data to delete)

3. `privileges_after_insert()`:
   - Clears all privileges (no effect, no existing data)
   - Checks if record exists for group=1, module=12: NO
   - Inserts new record

**Final State:**
```sql
-- base_group_privilege
INSERT INTO base_group_privilege (group_id, module_id, admin_privilege, index_privilege)
VALUES (1, 12, '8:4', NULL);

-- base_page_privilege: No changes (empty)
```

---

#### Scenario 4: Clear Both Tabs

**Initial State:**
```sql
-- base_group_privilege
| id | group_id | module_id | admin_privilege | index_privilege |
|----|----------|-----------|-----------------|-----------------|
| 1  | 1        | 12        | 8:4             | 8               |

-- base_page_privilege
| id | group_id | module_id | target_table | target_field_name | target_field_values |
|----|----------|-----------|--------------|-------------------|---------------------|
| 1  | 1        | 12        | users        | department        | sales::marketing    |
```

**User Action:**
- Uncheck all module checkboxes
- Clear all mapping selections
- Submit form

**Request Data:**
```php
[
    // NO 'modules' key
    // NO 'rolePages' key
]
```


**Processing:**

1. `privileges_before_insert()`:
   - No 'modules' key found
   - Sets "setnull" marker:
   ```php
   $this->roles = [
       'setnull' => ['group_id' => 1]
   ];
   ```

2. `mapping_before_insert()`:
   - No 'rolePages' key found
   - Builds empty `$roles = []`
   - Calls `insert_process([])` which triggers DELETE

3. `privileges_after_insert()`:
   - Detects "setnull" marker
   - Updates all privileges to NULL

**Final State:**
```sql
-- base_group_privilege (record preserved, values set to NULL)
UPDATE base_group_privilege 
SET admin_privilege = NULL, index_privilege = NULL 
WHERE group_id = 1;

Result:
| id | group_id | module_id | admin_privilege | index_privilege |
|----|----------|-----------|-----------------|-----------------|
| 1  | 1        | 12        | NULL            | NULL            |

-- base_page_privilege (records deleted)
DELETE FROM base_page_privilege WHERE group_id = 1;

Result: Empty table for group_id = 1
```

---

#### Scenario 6: Clear Mapping Only, Keep Module

**Initial State:**
```sql
-- base_group_privilege
| id | group_id | module_id | admin_privilege | index_privilege |
|----|----------|-----------|-----------------|-----------------|
| 1  | 1        | 12        | 8:4             | 8               |

-- base_page_privilege
| id | group_id | module_id | target_table | target_field_name | target_field_values |
|----|----------|-----------|--------------|-------------------|---------------------|
| 1  | 1        | 12        | users        | department        | sales               |
```

**User Action:**
- Keep module checkboxes checked
- Clear all mapping selections
- Submit form


**Request Data:**
```php
[
    'modules' => [
        'admin_privilege' => [
            'admin.content.articles' => ['8' => '12', '4' => '12']
        ],
        'index_privilege' => [
            'admin.content.articles' => ['8' => '12']
        ]
    ]
    // NO 'rolePages' key
]
```

**Processing:**

1. `privileges_before_insert()`:
   ```php
   $this->roles = [
       ['group_id' => 1, 'module_id' => 12, 'admin_privilege' => '8:4', 'index_privilege' => '8']
   ];
   ```

2. `mapping_before_insert()`:
   - No 'rolePages' key found
   - Builds empty `$roles = []`
   - Calls `insert_process([])` which triggers DELETE

3. `privileges_after_insert()`:
   - Clears all privileges first
   - Checks if record exists: YES
   - Updates record with new values

**Final State:**
```sql
-- base_group_privilege (kept and updated)
| id | group_id | module_id | admin_privilege | index_privilege |
|----|----------|-----------|-----------------|-----------------|
| 1  | 1        | 12        | 8:4             | 8               |

-- base_page_privilege (deleted)
DELETE FROM base_page_privilege WHERE group_id = 1;

Result: Empty table for group_id = 1
```

---

#### Scenario 9: Update Both Tabs

**Initial State:**
```sql
-- base_group_privilege
| id | group_id | module_id | admin_privilege | index_privilege |
|----|----------|-----------|-----------------|-----------------|
| 1  | 1        | 12        | 8               | 8               |

-- base_page_privilege
| id | group_id | module_id | target_table | target_field_name | target_field_values |
|----|----------|-----------|--------------|-------------------|---------------------|
| 1  | 1        | 12        | users        | department        | sales               |
```


**User Action:**
- Add "Create" permission to module
- Change mapping from "sales" to "sales::marketing"
- Submit form

**Request Data:**
```php
[
    'modules' => [
        'admin_privilege' => [
            'admin.content.articles' => ['8' => '12', '4' => '12']  // Added '4'
        ],
        'index_privilege' => [
            'admin.content.articles' => ['8' => '12']
        ]
    ],
    'rolePages' => [
        'module' => ['admin.content.articles' => 12],
        'field_name' => ['admin.content.articles' => ['users' => ['department']]],
        'field_value' => ['admin.content.articles' => ['users' => ['department' => ['sales', 'marketing']]]]
    ]
]
```

**Processing:**

1. `privileges_before_insert()`:
   ```php
   $this->roles = [
       ['group_id' => 1, 'module_id' => 12, 'admin_privilege' => '8:4', 'index_privilege' => '8']
   ];
   ```

2. `mapping_before_insert()`:
   ```php
   $roles = [
       'admin.content.articles' => [
           'users' => [
               'department' => [
                   'group_id' => 1,
                   'module_id' => 12,
                   'target_table' => 'users',
                   'target_field_name' => 'department',
                   'target_field_values' => 'sales::marketing'
               ]
           ]
       ]
   ];
   ```
   - Calls `insert_process($roles)` which detects UPDATE needed

3. `privileges_after_insert()`:
   - Clears all privileges first
   - Updates record with new values


**Final State:**
```sql
-- base_group_privilege (updated)
UPDATE base_group_privilege 
SET admin_privilege = '8:4', index_privilege = '8' 
WHERE id = 1;

Result:
| id | group_id | module_id | admin_privilege | index_privilege |
|----|----------|-----------|-----------------|-----------------|
| 1  | 1        | 12        | 8:4             | 8               |

-- base_page_privilege (updated)
UPDATE base_page_privilege 
SET target_field_values = 'sales::marketing' 
WHERE id = 1;

Result:
| id | group_id | module_id | target_table | target_field_name | target_field_values |
|----|----------|-----------|--------------|-------------------|---------------------|
| 1  | 1        | 12        | users        | department        | sales::marketing    |
```

---

## Code Flow

### Complete Update Flow

```
1. User submits form
   ↓
2. GroupController::update($request, $id)
   ↓
3. DB::transaction(function() {
       ↓
   4. Update group basic info (name, alias, etc.)
       ↓
   5. set_data_before_insert($request, $id)
       ├→ privileges_before_insert($request, $group)
       │   └→ Prepares $this->roles
       │
       └→ mapping_before_insert($request, $group)
           └→ Calls insert_process() immediately
               └→ INSERT/UPDATE/DELETE in base_page_privilege
       ↓
   6. set_data_after_insert($this->roles)
       └→ privileges_after_insert($this->roles)
           └→ INSERT/UPDATE in base_group_privilege
       ↓
   7. Transaction commits
   })
   ↓
8. Cache invalidation (after successful commit)
   ↓
9. Redirect with success message
```


### Method Call Sequence

```php
// GroupController.php
public function update(Request $request, $id) {
    DB::transaction(function() use ($request, $id) {
        // 1. Update group basic info
        $group = Group::find($id);
        $group->update($request->only(['group_name', 'group_alias', 'group_info', 'active']));
        
        // 2. Process privileges
        $this->set_data_before_insert($request, $id);
        $this->set_data_after_insert($this->roles);
    });
    
    // 3. Invalidate cache
    Cache::forget("group_privileges_{$id}");
    
    return redirect()->back()->with('success', 'Group updated');
}

private function set_data_before_insert(Request $request, int $model_id) {
    $group = Group::find($model_id);
    
    // Process module privileges (prepares data)
    $this->privileges_before_insert($request, $group);
    
    // Process mapping privileges (executes immediately)
    $this->mapping_before_insert($request, $group);
}

private function set_data_after_insert($data) {
    // Execute module privileges operations
    $this->privileges_after_insert($data);
}
```

---

## Database Schema

### base_group_privilege

```sql
CREATE TABLE `base_group_privilege` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `admin_privilege` varchar(50) DEFAULT NULL,
  `index_privilege` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_module_unique` (`group_id`,`module_id`),
  KEY `group_id` (`group_id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `fk_privilege_group` FOREIGN KEY (`group_id`) REFERENCES `base_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_privilege_module` FOREIGN KEY (`module_id`) REFERENCES `base_module` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Points:**
- UNIQUE constraint on (group_id, module_id) prevents duplicates
- NULL values allowed for privilege columns
- CASCADE delete when group or module is deleted


### base_page_privilege

```sql
CREATE TABLE `base_page_privilege` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `target_table` varchar(100) NOT NULL,
  `target_field_name` varchar(100) NOT NULL,
  `target_field_values` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `group_id` (`group_id`),
  KEY `module_id` (`module_id`),
  KEY `target_table` (`target_table`),
  KEY `target_field_name` (`target_field_name`),
  CONSTRAINT `fk_page_privilege_group` FOREIGN KEY (`group_id`) REFERENCES `base_group` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_page_privilege_module` FOREIGN KEY (`module_id`) REFERENCES `base_module` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key Points:**
- No UNIQUE constraint (multiple mappings per group+module allowed)
- Indexes on commonly queried columns
- CASCADE delete when group or module is deleted
- target_field_values stored as TEXT (can be long)

---

## Troubleshooting

### Issue: Privileges Not Cleared

**Symptom:** User unchecks all checkboxes but privileges remain in database

**Possible Causes:**

1. **Form not submitting key:**
   - Check if 'modules' or 'rolePages' key is present in request
   - Some JavaScript frameworks remove empty keys

2. **Early return in code:**
   - Check if there are any early returns before `insert_process()` or `privileges_after_insert()`
   - This was the bug fixed in 2026-04-08

3. **Transaction rollback:**
   - Check logs for transaction errors
   - Ensure no exceptions thrown during processing

**Solution:**
```php
// Ensure these methods are ALWAYS called, even with empty data
$this->privileges_before_insert($request, $group);  // Always called
$this->mapping_before_insert($request, $group);     // Always called
$this->privileges_after_insert($this->roles);       // Always called
```


### Issue: Mapping Privileges Not Deleted

**Symptom:** User clears mapping selections but records remain in base_page_privilege

**Diagnosis Steps:**

1. **Check request data:**
   ```php
   \Log::debug('Request data', $request->all());
   ```
   - Verify 'rolePages' key is missing or empty

2. **Check if insert_process() is called:**
   ```php
   // In mapping_before_insert()
   \Log::debug('Calling insert_process', ['roles_count' => count($roles)]);
   $this->map()->insert_process($roles, $group);
   ```

3. **Check insert_process() logic:**
   - Verify DELETE operations are executed
   - Check for exceptions in logs

**Common Mistakes:**

```php
// BAD: Early return prevents DELETE
if (empty($roles)) {
    return;  // BUG: insert_process() never called!
}
$this->map()->insert_process($roles, $group);

// GOOD: Always call insert_process()
$this->map()->insert_process($roles, $group);  // Empty $roles triggers DELETE
```

### Issue: Duplicate Records

**Symptom:** Multiple records for same group+module+table+field

**Cause:** Missing UNIQUE constraint or logic error in insert_process()

**Solution:**

1. **Add application-level check:**
   ```php
   // In insert_process()
   $existing = canvastack_query($this->table)
       ->where('group_id', $group->id)
       ->where('module_id', $module_id)
       ->where('target_table', $table_name)
       ->where('target_field_name', $field_name)
       ->first();
   
   if ($existing) {
       // UPDATE
   } else {
       // INSERT
   }
   ```

2. **Add database constraint:**
   ```sql
   ALTER TABLE base_page_privilege 
   ADD UNIQUE KEY `group_module_table_field_unique` 
   (`group_id`, `module_id`, `target_table`, `target_field_name`);
   ```


### Issue: Cache Not Invalidated

**Symptom:** Changes saved to database but not reflected in UI

**Cause:** Cache not cleared after update

**Solution:**

```php
// In GroupController::update()
DB::transaction(function() {
    // ... update operations ...
});

// IMPORTANT: Invalidate cache AFTER transaction commits
Cache::forget("group_privileges_{$group_id}");
Cache::forget("mapping_privileges_{$group_id}");
Cache::tags(['groups', 'privileges'])->flush();
```

**Best Practice:**
- Always invalidate cache AFTER transaction commits
- Use cache tags for bulk invalidation
- Log cache invalidation for debugging

### Issue: Transaction Deadlock

**Symptom:** Random transaction failures with deadlock errors

**Cause:** Nested transactions or long-running operations

**Solution:**

1. **Avoid nested transactions:**
   ```php
   // BAD: Nested transaction
   DB::transaction(function() {
       DB::transaction(function() {  // NESTED!
           // ...
       });
   });
   
   // GOOD: Single transaction
   DB::transaction(function() {
       // All operations here
   });
   ```

2. **Keep transactions short:**
   - Only include database operations
   - Move cache invalidation outside transaction
   - Avoid external API calls inside transaction

3. **Use proper isolation level:**
   ```php
   DB::transaction(function() {
       // ...
   }, 3, 'READ COMMITTED');  // Less strict than default
   ```

---

## Testing

### Unit Test Examples

See `tests/Unit/MappingPageClearAllBugfixTest.php` for comprehensive test coverage:

- ✓ Clear both module and mapping privileges
- ✓ Clear mapping only, keep module
- ✓ Clear mapping when no module privileges
- ✓ Normal mapping update still works


### Manual Testing Checklist

**Module Privileges Tab:**

- [ ] Add new module privileges (no existing data)
- [ ] Update existing module privileges
- [ ] Clear all module privileges (uncheck all)
- [ ] Partial clear (uncheck some, keep others)
- [ ] Verify NULL values in database after clear
- [ ] Verify records preserved (not deleted)

**Mapping Page Privileges Tab:**

- [ ] Add new mapping (no existing data)
- [ ] Update existing mapping values
- [ ] Clear all mappings (remove all selections)
- [ ] Partial clear (remove some fields, keep others)
- [ ] Verify records deleted from database after clear
- [ ] Verify normal update still works

**Combined Scenarios:**

- [ ] Add both tabs from scratch
- [ ] Clear both tabs simultaneously
- [ ] Clear one tab, keep the other
- [ ] Update both tabs simultaneously
- [ ] Verify transaction atomicity (all or nothing)

**Edge Cases:**

- [ ] Submit form with invalid module route
- [ ] Submit form with invalid table/field names
- [ ] Submit form with very long field values
- [ ] Submit form with special characters in values
- [ ] Test with multiple modules/mappings
- [ ] Test with concurrent updates (multiple users)

---

## Best Practices

### For Developers

1. **Always Process Data:**
   - Never add early returns that skip processing
   - Always call `insert_process()` and `privileges_after_insert()`
   - Empty data is valid data (means "clear all")

2. **Use Transactions:**
   - Wrap all privilege updates in a single transaction
   - Invalidate cache only after transaction commits
   - Handle exceptions properly

3. **Log Important Operations:**
   - Log when privileges are cleared
   - Log when "setnull" marker is detected
   - Log database operation counts (inserted, updated, deleted)


4. **Maintain Consistency:**
   - Module privileges and mapping privileges should behave similarly
   - Both should support "clear all" functionality
   - Document any differences in behavior

5. **Write Tests:**
   - Test all scenarios in the matrix
   - Test edge cases and error conditions
   - Use RefreshDatabase trait for isolation

### For Users

1. **Understanding "Clear All":**
   - Unchecking all checkboxes = remove all privileges
   - Clearing all selections = remove all mappings
   - Changes are saved immediately on submit

2. **Module Privileges:**
   - At least one privilege type should be selected
   - Read (8) is usually the minimum required
   - Combine privileges for full access (8:4:2:1)

3. **Mapping Privileges:**
   - Optional feature for data-level filtering
   - No mapping = access all data
   - Multiple values = OR condition (sales OR marketing)

4. **Best Practices:**
   - Test changes in development first
   - Document privilege requirements for each role
   - Review privileges regularly
   - Use descriptive group names

---

## Changelog

### 2026-04-08: Bugfix - Mapping Page "Clear All" Not Working

**Issue:**
- Users could not clear all mapping privileges
- Unchecking all selections left old data in database
- Root cause: Early returns prevented `insert_process()` from being called

**Fix:**
- Removed all early returns in `mapping_before_insert()`
- Always call `insert_process()`, even with empty `$roles` array
- Empty array triggers DELETE operations as intended

**Impact:**
- Scenarios 4, 6, 13 now work correctly
- All 13 scenarios in matrix now functional
- Behavior consistent with module privileges

**Files Changed:**
- `vendor/canvastack/canvastack/src/Controllers/Admin/System/Includes/MappingPage.php`

**Tests Added:**
- `tests/Unit/MappingPageClearAllBugfixTest.php`

---

## References

- **Code Files:**
  - `vendor/canvastack/canvastack/src/Controllers/Admin/System/GroupController.php`
  - `vendor/canvastack/canvastack/src/Controllers/Admin/System/Includes/Privileges.php`
  - `vendor/canvastack/canvastack/src/Controllers/Admin/System/Includes/MappingPage.php`
  - `vendor/canvastack/canvastack/src/Models/Admin/System/MappingPage.php`

- **Test Files:**
  - `tests/Unit/MappingPageClearAllBugfixTest.php`
  - `tests/Unit/MappingBeforeInsertOptimizationTest.php`

- **Documentation:**
  - `docs/CORE/GROUP/COMPLETE_BEHAVIOR_ANALYSIS.md`
    
---

**End of Document**
