# 🔍 DATATABLE INSPECTOR MODULE

**Version**: 1.0.0  
**Purpose**: Comprehensive diagnostic and debugging utilities for Datatable operations  
**Status**: Development/Debug Tool (Production Safe)

---

## 📁 **MODULE STRUCTURE**

```
Inspector/
├── README.md                           # Main documentation (this file)
├── Core/
│   ├── 01_Inspector.php               # Main Inspector class
│   ├── 02_FeatureFlag.php             # Feature flag management
│   └── 03_InspectorConfig.php         # Configuration management
├── Diagnostics/
│   ├── 01_ContextCapture.php          # Context data capture
│   ├── 02_DataDumper.php              # Data dumping utilities
│   └── 03_RouteAnalyzer.php           # Route information analysis
├── Storage/
│   ├── 01_FileManager.php             # File storage management
│   ├── 02_JsonFormatter.php           # JSON formatting utilities
│   └── 03_StorageCleanup.php          # Storage cleanup utilities
├── Testing/
│   ├── 01_ReplaySupport.php           # Test replay functionality
│   ├── 02_ScenarioProvider.php        # Test scenario generation
│   └── 03_ValidationHelper.php        # Validation utilities
├── Analysis/
│   ├── 01_DiffAnalyzer.php            # Difference analysis
│   ├── 02_PerformanceTracker.php      # Performance monitoring
│   └── 03_ReportGenerator.php         # Report generation
└── docs/
    ├── 01_OVERVIEW.md                 # Module overview
    ├── 02_USAGE_GUIDE.md              # Usage instructions
    ├── 03_API_REFERENCE.md            # API documentation
    ├── 04_CONFIGURATION.md            # Configuration guide
    ├── 05_TESTING_GUIDE.md            # Testing documentation
    ├── 06_TROUBLESHOOTING.md          # Common issues & solutions
    └── 07_CHANGELOG.md                # Version history
```

---

## 🎯 **MODULE PURPOSE**

### **Primary Functions**
1. **Diagnostic Logging**: Capture datatable context for debugging
2. **Development Support**: Provide debugging tools during development
3. **Test Support**: Enable replay testing with captured scenarios
4. **Performance Analysis**: Monitor datatable performance metrics
5. **Refactor Validation**: Support hybrid comparison during refactoring

### **Key Features**
- ✅ **Production Safe**: Only active in development/hybrid mode
- ✅ **Modular Design**: Separated by functionality
- ✅ **Comprehensive Logging**: Full context capture
- ✅ **Test Integration**: Seamless test support
- ✅ **Performance Monitoring**: Built-in metrics
- ✅ **Easy Configuration**: Flexible settings

---

## 🚀 **QUICK START**

### **Basic Usage**
```php
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\Inspector;

// Capture datatable context
Inspector::inspect([
    'table_name' => 'users',
    'model_type' => 'User',
    'columns' => [...],
    'filters' => [...],
    // ... other context data
]);
```

### **Configuration**
```php
// config/canvastack.php
'datatables' => [
    'inspector' => [
        'enabled' => env('CANVASTACK_INSPECTOR_ENABLED', false),
        'mode' => env('CANVASTACK_INSPECTOR_MODE', 'file'),
        'storage_path' => 'datatable-inspector',
        'max_files' => 100,
        'cleanup_days' => 7,
    ]
]
```

---

## 📋 **COMPONENT OVERVIEW**

### **Core Components**
- **Inspector**: Main entry point for all diagnostic operations
- **FeatureFlag**: Environment and configuration management
- **InspectorConfig**: Centralized configuration handling

### **Diagnostic Components**
- **ContextCapture**: Captures comprehensive datatable context
- **DataDumper**: Handles data serialization and storage
- **RouteAnalyzer**: Analyzes request routing information

### **Storage Components**
- **FileManager**: Manages file operations and organization
- **JsonFormatter**: Formats data for human readability
- **StorageCleanup**: Automatic cleanup of old diagnostic files

### **Testing Components**
- **ReplaySupport**: Enables test scenario replay
- **ScenarioProvider**: Generates test scenarios from captured data
- **ValidationHelper**: Assists in test validation

### **Analysis Components**
- **DiffAnalyzer**: Compares different datatable outputs
- **PerformanceTracker**: Monitors performance metrics
- **ReportGenerator**: Creates comprehensive reports

---

## 🔧 **INTEGRATION POINTS**

### **Orchestrator Integration**
```php
// In Datatables.php (orchestrator)
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Core\Inspector;

// Capture context at key points
Inspector::inspect($contextData);
```

### **Test Integration**
```php
// In test files
use Canvastack\Canvastack\Library\Components\Table\Craft\Canvaser\Inspector\Testing\ReplaySupport;

// Use captured scenarios for testing
$scenarios = ReplaySupport::getScenarios();
```

---

## 📊 **PERFORMANCE IMPACT**

### **Development Mode**
- **Memory**: ~2-5MB additional usage
- **Storage**: ~1-10MB per diagnostic session
- **Performance**: <1% impact on datatable operations

### **Production Mode**
- **Memory**: 0 (disabled)
- **Storage**: 0 (disabled)
- **Performance**: 0 (disabled)

---

## 🛡️ **SECURITY CONSIDERATIONS**

### **Data Protection**
- ✅ **No Sensitive Data**: Excludes passwords, tokens, personal data
- ✅ **Local Only**: Only runs in development environments
- ✅ **Automatic Cleanup**: Old files automatically removed
- ✅ **Access Control**: Files stored in protected directories

### **Production Safety**
- ✅ **Environment Checks**: Multiple layers of environment validation
- ✅ **Feature Flags**: Can be completely disabled via configuration
- ✅ **Error Handling**: Silent failures prevent production disruption
- ✅ **Resource Limits**: Built-in limits prevent resource exhaustion

---

## 📝 **MAINTENANCE**

### **Regular Tasks**
- **Storage Cleanup**: Automatic cleanup of old diagnostic files
- **Performance Review**: Monitor impact on development workflow
- **Documentation Updates**: Keep documentation current with changes
- **Security Audit**: Regular review of captured data types

### **Upgrade Path**
- **Backward Compatibility**: Maintained across versions
- **Migration Tools**: Provided for major version changes
- **Deprecation Notices**: Clear communication of changes
- **Testing Support**: Comprehensive test coverage

---

## 🎖️ **VERSION HISTORY**

### **v1.0.0** (Current)
- ✅ Initial modular structure
- ✅ Core diagnostic functionality
- ✅ Test integration support
- ✅ Comprehensive documentation
- ✅ Production safety measures

---

## 📞 **SUPPORT**

### **Documentation**
- **Overview**: `docs/01_OVERVIEW.md`
- **Usage Guide**: `docs/02_USAGE_GUIDE.md`
- **API Reference**: `docs/03_API_REFERENCE.md`
- **Troubleshooting**: `docs/06_TROUBLESHOOTING.md`

### **Common Issues**
- **Storage Permission**: Ensure `storage/app` is writable
- **Environment Detection**: Verify `APP_ENV=local` or hybrid mode
- **File Limits**: Check storage space and file limits
- **Performance**: Monitor memory usage in development

---

**Status**: ✅ **READY FOR USE**  
**Compatibility**: Laravel 8+, PHP 8.0+  
**License**: Same as parent project  
**Maintainer**: Canvastack Development Team