# 🔍 **ADVANCED FILTER MODAL SYSTEM**

## 📋 **TABLE OF CONTENTS**
1. [Feature Overview](#feature-overview)
2. [Technical Implementation](#technical-implementation)
3. [Component Architecture](#component-architecture)
4. [Data Flow](#data-flow)
5. [Configuration Options](#configuration-options)
6. [Usage Examples](#usage-examples)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 **FEATURE OVERVIEW**

The Advanced Filter Modal System provides a sophisticated, user-friendly interface for filtering table data. It features a modal-based UI that allows users to apply multiple filters simultaneously with real-time feedback.

### **Key Features:**
✅ **Modal-based Interface** - Clean, focused filtering experience  
✅ **Multi-field Filtering** - Filter by multiple columns simultaneously  
✅ **Dynamic Form Generation** - Automatically generates appropriate input types  
✅ **Real-time Validation** - Instant feedback on filter values  
✅ **Responsive Design** - Works seamlessly on mobile and desktop  
✅ **Persistent Filters** - Maintains filter state across page reloads  
✅ **Clear All Functionality** - Easy reset of all applied filters  

---

## 🏗️ **TECHNICAL IMPLEMENTATION**

### **Core Components:**

#### **1. Filter Modal HTML Structure**
```html
<div id="filterModal_{table_id}" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fa fa-filter"></i> Filter Data
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="filterForm_{table_id}">
                    <!-- Dynamic filter fields generated here -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Field Name</label>
                                <input type="text" class="form-control" name="field_name">
                            </div>
                        </div>
                        <!-- More fields... -->
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning" id="clearFilters_{table_id}">
                    <i class="fa fa-refresh"></i> Clear All
                </button>
                <button type="button" class="btn btn-primary" id="applyFilters_{table_id}">
                    <i class="fa fa-check"></i> Apply Filters
                </button>
            </div>
        </div>
    </div>
</div>
```

#### **2. JavaScript Event Handlers**
```javascript
// Filter Modal Event Handlers
$(document).on('click', '.btn_filter_modal', function(e) {
    e.preventDefault();
    var modalTarget = $(this).data('target');
    $(modalTarget).modal('show');
});

// Apply Filters Handler
$(document).on('click', '[id^="applyFilters_"]', function() {
    var tableId = $(this).attr('id').replace('applyFilters_', '');
    var formData = $('#filterForm_' + tableId).serialize();
    
    // Apply filters to DataTable
    if (window.canvastack_datatables_config && window.canvastack_datatables_config[tableId]) {
        var table = $('#' + tableId).DataTable();
        
        // Parse form data and apply filters
        var filters = parseFormData(formData);
        applyFiltersToTable(table, filters);
        
        // Close modal
        $('#filterModal_' + tableId).modal('hide');
        
        // Show success message
        showFilterSuccessMessage(Object.keys(filters).length);
    }
});

// Clear Filters Handler
$(document).on('click', '[id^="clearFilters_"]', function() {
    var tableId = $(this).attr('id').replace('clearFilters_', '');
    
    // Clear form
    $('#filterForm_' + tableId)[0].reset();
    
    // Clear DataTable filters
    if (window.canvastack_datatables_config && window.canvastack_datatables_config[tableId]) {
        var table = $('#' + tableId).DataTable();
        table.search('').columns().search('').draw();
    }
    
    // Close modal
    $('#filterModal_' + tableId).modal('hide');
    
    // Show clear message
    showFilterClearMessage();
});
```

---

## 🏛️ **COMPONENT ARCHITECTURE**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        FILTER MODAL ARCHITECTURE                           │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │   TRIGGER       │    │   MODAL UI      │    │   PROCESSING            │ │
│  │   COMPONENT     │    │   COMPONENT     │    │   COMPONENT             │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
│           │                       │                         │               │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────────────────┐ │
│  │ • Filter Button │    │ • Modal HTML    │    │ • Form Processing       │ │
│  │ • Icon & Text   │    │ • Dynamic Form  │    │ • DataTable Integration │ │
│  │ • Event Binding │    │ • Input Fields  │    │ • Query Building        │ │
│  │ • Modal Target  │    │ • Validation    │    │ • Result Rendering      │ │
│  └─────────────────┘    └─────────────────┘    └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────────────┘
```

### **Component Breakdown:**

#### **1. Filter Button Generator**
**File**: `Library/Components/Utility/Html/TableUi.php`
```php
public static function generateFilterButton(string $tableId, array $filterConfig): string
{
    $modalId = 'filterModal_' . $tableId;
    
    $button = '<button type="button" class="btn btn-info btn-sm btn_filter_modal" ' .
              'data-toggle="modal" data-target="#' . $modalId . '" ' .
              'title="Filter Data">' .
              '<i class="fa fa-filter"></i> Filter' .
              '</button>';
    
    return $button;
}
```

#### **2. Modal HTML Generator**
**File**: `Library/Components/Utility/Html/TableUi.php`
```php
public static function generateFilterModal(string $tableId, array $columns): string
{
    $modalId = 'filterModal_' . $tableId;
    $formId = 'filterForm_' . $tableId;
    
    $modalHtml = '<div id="' . $modalId . '" class="modal fade" role="dialog">';
    // ... complete modal structure
    $modalHtml .= '</div>';
    
    return $modalHtml;
}
```

#### **3. JavaScript Integration**
**File**: `Library/Components/Table/Craft/Scripts.php`
```php
private function generateFilterModalScript(string $tableId): string
{
    return "
    // Filter Modal Handlers for table: {$tableId}
    $(document).on('click', '.btn_filter_modal[data-target=\"#filterModal_{$tableId}\"]', function(e) {
        e.preventDefault();
        $('#filterModal_{$tableId}').modal('show');
    });
    
    // Apply filters
    $('#applyFilters_{$tableId}').on('click', function() {
        // Filter application logic
    });
    ";
}
```

---

## 🔄 **DATA FLOW**

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           FILTER DATA FLOW                                 │
└─────────────────────────────────────────────────────────────────────────────┘

1. USER INTERACTION
   ┌─────────────┐
   │    User     │ ──── Clicks Filter Button ────┐
   │   Clicks    │                                │
   └─────────────┘                                ▼
                                         ┌─────────────────┐
                                         │   JavaScript    │
                                         │ Event Handler   │
                                         └─────────────────┘
                                                  │
2. MODAL DISPLAY                                 │
   ┌──────────────────────────────────────────────▼──────────────────────────┐
   │ Modal opens with dynamically generated form fields                      │
   │ • Text inputs for string fields                                         │
   │ • Select dropdowns for enum/relationship fields                         │
   │ • Date pickers for date fields                                          │
   │ • Number inputs for numeric fields                                      │
   └──────────────────────────────────────────────┬──────────────────────────┘
                                                  │
3. USER INPUT                                     ▼
   ┌─────────────┐                       ┌─────────────────┐
   │    User     │ ──── Fills Form ─────▶│   Form Fields   │
   │ Enters Data │                       │   Validation    │
   └─────────────┘                       └─────────────────┘
                                                  │
4. FORM SUBMISSION                                ▼
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                        FILTER PROCESSING                                 │
   ├──────────────────────────────────────────────────────────────────────────┤
   │                                                                          │
   │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌───────────┐ │
   │  │   Form      │    │   Parse     │    │   Build     │    │  Apply    │ │
   │  │Serialization│───▶│   Data      │───▶│  Filters    │───▶│ to Table  │ │
   │  └─────────────┘    └─────────────┘    └─────────────┘    └───────────┘ │
   │         │                   │                   │               │       │
   │         ▼                   ▼                   ▼               ▼       │
   │  ┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌───────────┐ │
   │  │ Extract     │    │ Validate    │    │ DataTable   │    │ Refresh   │ │
   │  │ Field/Value │    │ Input Data  │    │ Column      │    │ Table     │ │
   │  │ Pairs       │    │             │    │ Filters     │    │ Display   │ │
   │  └─────────────┘    └─────────────┘    └─────────────┘    └───────────┘ │
   └──────────────────────────────────────────────┬───────────────────────────┘
                                                  │
5. TABLE UPDATE                                   ▼
   ┌─────────────┐                       ┌─────────────────┐
   │   Updated   │ ◄──── New Data ───────│   DataTable     │
   │    Table    │                       │   Redraw        │
   │   Display   │                       └─────────────────┘
   └─────────────┘
         │
6. USER FEEDBACK                         
   ┌──────────────────────────────────────────────────────────────────────────┐
   │                         SUCCESS FEEDBACK                                 │
   ├──────────────────────────────────────────────────────────────────────────┤
   │ • Modal closes automatically                                             │
   │ • Success message displays: "X filters applied successfully"             │
   │ • Table shows filtered results                                           │
   │ • Filter button shows active state (if filters applied)                 │
   │ • Pagination resets to page 1                                           │
   └──────────────────────────────────────────────────────────────────────────┘
```

---

## ⚙️ **CONFIGURATION OPTIONS**

### **Basic Configuration:**
```php
$filterConfig = [
    'enabled' => true,                    // Enable/disable filtering
    'modal_size' => 'modal-lg',          // Modal size (sm, md, lg, xl)
    'button_text' => 'Filter',           // Button label
    'button_icon' => 'fa-filter',        // Button icon
    'button_class' => 'btn-info',        // Button styling
    'auto_close' => true,                // Close modal after apply
    'show_clear' => true,                // Show clear all button
    'persistent' => false,               // Persist filters across sessions
];
```

### **Field Configuration:**
```php
$fieldConfig = [
    'name' => [
        'type' => 'text',                // Input type
        'label' => 'Name',               // Field label
        'placeholder' => 'Enter name...',// Placeholder text
        'required' => false,             // Required validation
        'validation' => 'string|max:255',// Laravel validation rules
    ],
    'status' => [
        'type' => 'select',
        'label' => 'Status',
        'options' => [                   // Select options
            'active' => 'Active',
            'inactive' => 'Inactive',
        ],
        'multiple' => false,             // Allow multiple selection
    ],
    'created_at' => [
        'type' => 'daterange',
        'label' => 'Created Date',
        'format' => 'Y-m-d',            // Date format
    ],
];
```

### **Advanced Configuration:**
```php
$advancedConfig = [
    'ajax_url' => '/api/table/filter',   // Custom filter endpoint
    'method' => 'POST',                  // HTTP method
    'debounce' => 300,                   // Input debounce (ms)
    'min_length' => 2,                   // Minimum search length
    'max_filters' => 10,                 // Maximum concurrent filters
    'cache_results' => true,             // Cache filter results
    'export_filtered' => true,           // Export only filtered data
];
```

---

## 💻 **USAGE EXAMPLES**

### **1. Basic Filter Implementation:**
```php
// Controller
public function index()
{
    $table = canvastack_table([
        'model' => User::class,
        'columns' => ['name', 'email', 'status', 'created_at'],
        'filters' => [
            'name' => ['type' => 'text'],
            'email' => ['type' => 'text'],
            'status' => [
                'type' => 'select',
                'options' => ['active' => 'Active', 'inactive' => 'Inactive']
            ],
        ],
        'filter_config' => [
            'button_text' => 'Filter Users',
            'modal_size' => 'modal-lg',
        ]
    ]);
    
    return view('users.index', compact('table'));
}
```

### **2. Advanced Filter with Relationships:**
```php
// Controller with relationship filtering
public function index()
{
    $table = canvastack_table([
        'model' => Order::class,
        'columns' => ['id', 'customer.name', 'total', 'status', 'created_at'],
        'filters' => [
            'customer_name' => [
                'type' => 'text',
                'label' => 'Customer Name',
                'column' => 'customer.name',  // Relationship column
            ],
            'total_range' => [
                'type' => 'number_range',
                'label' => 'Total Amount',
                'min' => 0,
                'max' => 10000,
            ],
            'status' => [
                'type' => 'select',
                'options' => Order::getStatusOptions(),
                'multiple' => true,
            ],
            'date_range' => [
                'type' => 'daterange',
                'label' => 'Order Date',
                'format' => 'Y-m-d',
            ],
        ],
    ]);
    
    return view('orders.index', compact('table'));
}
```

### **3. Custom Filter Processing:**
```php
// Custom filter handler
public function filter(Request $request)
{
    $query = User::query();
    
    // Apply custom filter logic
    if ($request->filled('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }
    
    if ($request->filled('status')) {
        $query->whereIn('status', (array) $request->status);
    }
    
    if ($request->filled('date_range')) {
        $dates = explode(' - ', $request->date_range);
        $query->whereBetween('created_at', [
            Carbon::parse($dates[0])->startOfDay(),
            Carbon::parse($dates[1])->endOfDay(),
        ]);
    }
    
    $results = $query->paginate(25);
    
    return response()->json([
        'data' => $results->items(),
        'total' => $results->total(),
        'filtered' => $results->count(),
    ]);
}
```

---

## 🎨 **UI CUSTOMIZATION**

### **Modal Styling:**
```css
/* Custom filter modal styles */
.filter-modal .modal-dialog {
    max-width: 800px;
}

.filter-modal .form-group {
    margin-bottom: 1.5rem;
}

.filter-modal .btn-group {
    width: 100%;
}

.filter-modal .modal-footer {
    justify-content: space-between;
}

/* Active filter button state */
.btn_filter_modal.active {
    background-color: #28a745;
    border-color: #28a745;
}

.btn_filter_modal.active::after {
    content: " (Active)";
    font-size: 0.8em;
}
```

### **Responsive Design:**
```css
/* Mobile optimizations */
@media (max-width: 768px) {
    .filter-modal .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
    
    .filter-modal .row > .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .filter-modal .modal-footer {
        flex-direction: column;
    }
    
    .filter-modal .modal-footer .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
```

---

## 🐛 **TROUBLESHOOTING**

### **Common Issues:**

#### **1. Modal Not Opening**
```javascript
// Debug: Check if modal exists in DOM
console.log('Modal exists:', $('#filterModal_tableid').length > 0);

// Debug: Check event binding
$(document).on('click', '.btn_filter_modal', function(e) {
    console.log('Filter button clicked:', $(this).data('target'));
});

// Solution: Ensure modal is appended to body
$('body').append(modalHtml);
```

#### **2. Filters Not Applied**
```javascript
// Debug: Check form data
$('#applyFilters_tableid').on('click', function() {
    var formData = $('#filterForm_tableid').serialize();
    console.log('Form data:', formData);
});

// Debug: Check DataTable instance
if ($.fn.DataTable.isDataTable('#tableid')) {
    var table = $('#tableid').DataTable();
    console.log('DataTable instance:', table);
}
```

#### **3. Form Validation Issues**
```javascript
// Debug: Check field validation
$('#filterForm_tableid input, #filterForm_tableid select').each(function() {
    console.log('Field:', $(this).attr('name'), 'Value:', $(this).val());
});

// Solution: Add proper validation
function validateFilterForm(formId) {
    var isValid = true;
    $('#' + formId + ' [required]').each(function() {
        if (!$(this).val()) {
            $(this).addClass('is-invalid');
            isValid = false;
        } else {
            $(this).removeClass('is-invalid');
        }
    });
    return isValid;
}
```

### **Performance Optimization:**

#### **1. Debounce Input Events**
```javascript
// Debounce filter input for better performance
var filterTimeout;
$('#filterForm_tableid input').on('input', function() {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(function() {
        // Apply filter logic
    }, 300);
});
```

#### **2. Cache Filter Results**
```javascript
// Cache filter results to avoid repeated queries
var filterCache = {};
function applyFilters(filters) {
    var cacheKey = JSON.stringify(filters);
    if (filterCache[cacheKey]) {
        displayResults(filterCache[cacheKey]);
    } else {
        // Fetch new results and cache
        fetchFilterResults(filters).then(function(results) {
            filterCache[cacheKey] = results;
            displayResults(results);
        });
    }
}
```

---

## 📊 **PERFORMANCE METRICS**

### **Expected Performance:**
- **Modal Open Time**: < 200ms
- **Filter Application**: < 1 second for 10,000 records
- **Form Validation**: < 50ms
- **Memory Usage**: < 10MB additional

### **Optimization Tips:**
1. **Lazy Load Options**: Load select options only when needed
2. **Debounce Inputs**: Prevent excessive API calls
3. **Cache Results**: Store frequently used filter combinations
4. **Minimize DOM Manipulation**: Batch DOM updates
5. **Use Event Delegation**: Efficient event handling for dynamic content

---

*This documentation covers the complete Advanced Filter Modal System. The system provides a powerful, user-friendly interface for filtering table data with extensive customization options and robust error handling.*