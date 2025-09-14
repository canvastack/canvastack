# 🎨 **TEMPLATE SYSTEM & ASSET MANAGEMENT**

## 📋 **TABLE OF CONTENTS**
1. [Feature Overview](#feature-overview)
2. [Template Configuration](#template-configuration)
3. [Asset Loading System](#asset-loading-system)
4. [Dependency Management](#dependency-management)
5. [Performance Optimization](#performance-optimization)
6. [Customization Guide](#customization-guide)
7. [CDN vs Local Assets](#cdn-vs-local-assets)
8. [Troubleshooting](#troubleshooting)

---

## 🎯 **FEATURE OVERVIEW**

The Template System & Asset Management provides a comprehensive solution for managing CSS, JavaScript, and other assets required by the Canvastack Table System. It supports both local and CDN assets, dependency resolution, and performance optimization.

### **Key Features:**
✅ **Flexible Asset Loading** - Support for local and CDN resources  
✅ **Dependency Management** - Automatic dependency resolution  
✅ **Performance Optimization** - Asset bundling and caching  
✅ **Template Inheritance** - Hierarchical template system  
✅ **Conditional Loading** - Load assets based on requirements  
✅ **Version Management** - Asset versioning and cache busting  
✅ **Environment Support** - Different configs for dev/prod  
✅ **Plugin Integration** - Easy integration with additional libraries  

---

## 🏗️ **TEMPLATE CONFIGURATION**

### **Main Configuration File:**
**File**: `config/canvastack.templates.php`

```php
<?php
return [
    'admin' => [
        'default' => [
            'position' => [
                'top' => [
                    'js' => [
                        'vendor/plugins/nodes/jquery/dist/jquery.min.js',
                        'vendor/plugins/nodes/popper.js/dist/umd/popper.min.js',
                        'vendor/plugins/nodes/bootstrap/dist/js/bootstrap.min.js',
                        'vendor/plugins/nodes/ion-sound/js/ion.sound.min.js',
                        'js/sidebar.js',
                        'js/firscripts.js',
                    ],
                    'css' => [
                        'vendor/plugins/nodes/bootstrap/dist/css/bootstrap.css',
                    ],
                ],
                'bottom' => [
                    'first' => [
                        'js' => [
                            'vendor/plugins/jquery-ui/jquery-ui.min.js',
                            'vendor/plugins/jquery-cookie/jquery.cookie.js',
                            'js/metisMenu.min.js',
                            'vendor/plugins/nodes/owl.carousel/dist/owl.carousel.min.js',
                            'vendor/plugins/nodes/jquery-slimscroll/jquery.slimscroll.min.js',
                            'vendor/plugins/nodes/slicknav/dist/jquery.slicknav.min.js',
                            'vendor/plugins/jquery-nicescroll/jquery.nicescroll.min.js',
                        ],
                        'css' => ['css/config.css'],
                    ],
                    'last' => [
                        'js' => [
                            'js/plugins.js',
                            'js/scripts.js',
                            'js/diyscripts.js',
                        ],
                        'css' => ['css/app.css'],
                    ],
                ],
            ],

            // DataTables Configuration
            'datatable' => [
                'js' => [
                    'vendor/DataTables/js/datatables.min.js',
                    'vendor/DataTables/js/pdfmake.js',
                    'vendor/DataTables/js/vfs_fonts.js',
                    'js/datatables/filter.js',
                ],
                'css' => [
                    'vendor/DataTables/css/datatables.css',
                ],
            ],

            // Form Components
            'textarea' => [
                'js' => [
                    'vendor/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',
                    'js/textarea.js',
                ],
                'css' => [null],
            ],

            'select' => [
                'js' => ['vendor/plugins/nodes/chosen-js/chosen.jquery.min.js'],
                'css' => ['vendor/plugins/nodes/chosen-js/chosen.min.css'],
            ],

            'date' => [
                'js' => [
                    'vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.full.min.js',
                    'last:js/form.picker.js',
                ],
                'css' => ['vendor/plugins/nodes/jquery-datetimepicker/build/jquery.datetimepicker.min.css'],
            ],

            // Chart Libraries
            'highcharts' => [
                'js' => [
                    'vendor/plugins/highcharts/js/highcharts.js',
                    'vendor/plugins/highcharts/js/modules/exporting.js',
                ],
                'css' => [null],
            ],
        ],
    ],
];
```

---

## 📦 **ASSET LOADING SYSTEM**

### **1. Asset Loader Class:**
```php
class AssetLoader
{
    protected $config;
    protected $loadedAssets = [];
    protected $dependencies = [];
    
    public function __construct(array $config)
    {
        $this->config = $config;
    }
    
    public function loadAssets(string $template, array $components = []): array
    {
        $assets = [
            'css' => [],
            'js' => []
        ];
        
        // Load base template assets
        if (isset($this->config[$template])) {
            $assets = $this->mergeAssets($assets, $this->loadTemplate($template));
        }
        
        // Load component-specific assets
        foreach ($components as $component) {
            if (isset($this->config[$template][$component])) {
                $componentAssets = $this->loadComponent($template, $component);
                $assets = $this->mergeAssets($assets, $componentAssets);
            }
        }
        
        // Resolve dependencies
        $assets = $this->resolveDependencies($assets);
        
        // Remove duplicates and sort by priority
        $assets = $this->optimizeAssets($assets);
        
        return $assets;
    }
    
    protected function loadTemplate(string $template): array
    {
        $templateConfig = $this->config[$template]['default'];
        $assets = ['css' => [], 'js' => []];
        
        // Load positioned assets
        if (isset($templateConfig['position'])) {
            foreach ($templateConfig['position'] as $position => $positionAssets) {
                if (is_array($positionAssets)) {
                    foreach ($positionAssets as $type => $files) {
                        if (in_array($type, ['css', 'js']) && is_array($files)) {
                            foreach ($files as $file) {
                                if ($file !== null) {
                                    $assets[$type][] = [
                                        'file' => $file,
                                        'position' => $position,
                                        'priority' => $this->getPriority($position, $type)
                                    ];
                                }
                            }
                        }
                    }
                } else {
                    // Handle direct asset arrays
                    if (isset($positionAssets['css'])) {
                        foreach ($positionAssets['css'] as $file) {
                            if ($file !== null) {
                                $assets['css'][] = [
                                    'file' => $file,
                                    'position' => $position,
                                    'priority' => $this->getPriority($position, 'css')
                                ];
                            }
                        }
                    }
                    if (isset($positionAssets['js'])) {
                        foreach ($positionAssets['js'] as $file) {
                            if ($file !== null) {
                                $assets['js'][] = [
                                    'file' => $file,
                                    'position' => $position,
                                    'priority' => $this->getPriority($position, 'js')
                                ];
                            }
                        }
                    }
                }
            }
        }
        
        return $assets;
    }
    
    protected function loadComponent(string $template, string $component): array
    {
        $componentConfig = $this->config[$template]['default'][$component];
        $assets = ['css' => [], 'js' => []];
        
        foreach (['css', 'js'] as $type) {
            if (isset($componentConfig[$type]) && is_array($componentConfig[$type])) {
                foreach ($componentConfig[$type] as $file) {
                    if ($file !== null) {
                        $priority = 50; // Default priority for components
                        
                        // Handle priority prefixes (e.g., "last:file.js")
                        if (strpos($file, 'last:') === 0) {
                            $file = substr($file, 5);
                            $priority = 100;
                        } elseif (strpos($file, 'first:') === 0) {
                            $file = substr($file, 6);
                            $priority = 1;
                        }
                        
                        $assets[$type][] = [
                            'file' => $file,
                            'component' => $component,
                            'priority' => $priority
                        ];
                    }
                }
            }
        }
        
        return $assets;
    }
    
    protected function getPriority(string $position, string $type): int
    {
        $priorities = [
            'top' => ['css' => 10, 'js' => 10],
            'bottom' => [
                'first' => ['css' => 20, 'js' => 20],
                'last' => ['css' => 90, 'js' => 90]
            ]
        ];
        
        if (isset($priorities[$position][$type])) {
            return $priorities[$position][$type];
        }
        
        if (isset($priorities[$position]) && is_array($priorities[$position])) {
            foreach ($priorities[$position] as $subPosition => $subPriorities) {
                if (isset($subPriorities[$type])) {
                    return $subPriorities[$type];
                }
            }
        }
        
        return 50; // Default priority
    }
}
```

### **2. Asset Rendering:**
```php
class AssetRenderer
{
    protected $baseUrl;
    protected $version;
    
    public function __construct(string $baseUrl = '', string $version = '1.0.0')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->version = $version;
    }
    
    public function renderCss(array $cssAssets): string
    {
        $html = '';
        
        foreach ($cssAssets as $asset) {
            $url = $this->buildAssetUrl($asset['file'], 'css');
            $html .= '<link rel="stylesheet" type="text/css" href="' . $url . '">' . "\n";
        }
        
        return $html;
    }
    
    public function renderJs(array $jsAssets): string
    {
        $html = '';
        
        foreach ($jsAssets as $asset) {
            $url = $this->buildAssetUrl($asset['file'], 'js');
            $html .= '<script type="text/javascript" src="' . $url . '"></script>' . "\n";
        }
        
        return $html;
    }
    
    protected function buildAssetUrl(string $file, string $type): string
    {
        // Handle CDN URLs
        if (strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0) {
            return $file;
        }
        
        // Handle protocol-relative URLs
        if (strpos($file, '//') === 0) {
            return $file;
        }
        
        // Build local asset URL
        $url = $this->baseUrl . '/' . ltrim($file, '/');
        
        // Add version for cache busting
        if ($this->version) {
            $separator = strpos($url, '?') !== false ? '&' : '?';
            $url .= $separator . 'v=' . $this->version;
        }
        
        return $url;
    }
}
```

---

## 🔗 **DEPENDENCY MANAGEMENT**

### **1. Dependency Resolver:**
```php
class DependencyResolver
{
    protected $dependencies = [
        'bootstrap.min.js' => ['jquery.min.js', 'popper.min.js'],
        'datatables.min.js' => ['jquery.min.js'],
        'chosen.jquery.min.js' => ['jquery.min.js'],
        'jquery.datetimepicker.full.min.js' => ['jquery.min.js'],
        'form.picker.js' => ['jquery.min.js'],
    ];
    
    public function resolve(array $assets): array
    {
        $resolved = [];
        
        foreach ($assets as $type => $typeAssets) {
            $resolved[$type] = $this->resolveDependenciesForType($typeAssets);
        }
        
        return $resolved;
    }
    
    protected function resolveDependenciesForType(array $assets): array
    {
        $resolved = [];
        $processed = [];
        
        foreach ($assets as $asset) {
            $this->addAssetWithDependencies($asset, $resolved, $processed);
        }
        
        // Sort by priority
        usort($resolved, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return $resolved;
    }
    
    protected function addAssetWithDependencies(array $asset, array &$resolved, array &$processed): void
    {
        $filename = basename($asset['file']);
        
        // Skip if already processed
        if (in_array($filename, $processed)) {
            return;
        }
        
        // Add dependencies first
        if (isset($this->dependencies[$filename])) {
            foreach ($this->dependencies[$filename] as $dependency) {
                $dependencyAsset = [
                    'file' => $this->findAssetPath($dependency),
                    'priority' => $asset['priority'] - 1, // Higher priority than dependent
                    'dependency' => true
                ];
                
                $this->addAssetWithDependencies($dependencyAsset, $resolved, $processed);
            }
        }
        
        // Add the asset itself
        $resolved[] = $asset;
        $processed[] = $filename;
    }
    
    protected function findAssetPath(string $filename): string
    {
        // Common paths for dependencies
        $commonPaths = [
            'jquery.min.js' => 'vendor/plugins/nodes/jquery/dist/jquery.min.js',
            'popper.min.js' => 'vendor/plugins/nodes/popper.js/dist/umd/popper.min.js',
            'bootstrap.min.js' => 'vendor/plugins/nodes/bootstrap/dist/js/bootstrap.min.js',
        ];
        
        return $commonPaths[$filename] ?? $filename;
    }
}
```

### **2. Conflict Resolution:**
```php
class ConflictResolver
{
    protected $conflicts = [
        'jquery' => [
            'patterns' => ['/jquery.*\.js$/i'],
            'strategy' => 'latest_version'
        ],
        'bootstrap' => [
            'patterns' => ['/bootstrap.*\.js$/i'],
            'strategy' => 'latest_version'
        ],
        'datatables' => [
            'patterns' => ['/datatables.*\.js$/i'],
            'strategy' => 'merge_features'
        ]
    ];
    
    public function resolve(array $assets): array
    {
        foreach ($this->conflicts as $library => $config) {
            $assets = $this->resolveLibraryConflicts($assets, $library, $config);
        }
        
        return $assets;
    }
    
    protected function resolveLibraryConflicts(array $assets, string $library, array $config): array
    {
        $conflictingAssets = [];
        $nonConflictingAssets = [];
        
        // Identify conflicting assets
        foreach ($assets as $type => $typeAssets) {
            foreach ($typeAssets as $index => $asset) {
                $isConflicting = false;
                
                foreach ($config['patterns'] as $pattern) {
                    if (preg_match($pattern, $asset['file'])) {
                        $conflictingAssets[$type][] = $asset;
                        $isConflicting = true;
                        break;
                    }
                }
                
                if (!$isConflicting) {
                    $nonConflictingAssets[$type][] = $asset;
                }
            }
        }
        
        // Apply resolution strategy
        $resolvedAssets = $this->applyResolutionStrategy($conflictingAssets, $config['strategy']);
        
        // Merge back with non-conflicting assets
        foreach ($resolvedAssets as $type => $typeAssets) {
            if (!isset($nonConflictingAssets[$type])) {
                $nonConflictingAssets[$type] = [];
            }
            $nonConflictingAssets[$type] = array_merge($nonConflictingAssets[$type], $typeAssets);
        }
        
        return $nonConflictingAssets;
    }
    
    protected function applyResolutionStrategy(array $conflictingAssets, string $strategy): array
    {
        switch ($strategy) {
            case 'latest_version':
                return $this->keepLatestVersion($conflictingAssets);
            case 'merge_features':
                return $this->mergeFeatures($conflictingAssets);
            default:
                return $this->keepFirst($conflictingAssets);
        }
    }
}
```

---

## ⚡ **PERFORMANCE OPTIMIZATION**

### **1. Asset Bundling:**
```php
class AssetBundler
{
    protected $bundlePath;
    protected $publicPath;
    
    public function __construct(string $bundlePath, string $publicPath)
    {
        $this->bundlePath = $bundlePath;
        $this->publicPath = $publicPath;
    }
    
    public function bundle(array $assets, string $bundleName): array
    {
        $bundledAssets = ['css' => [], 'js' => []];
        
        foreach (['css', 'js'] as $type) {
            if (!empty($assets[$type])) {
                $bundleFile = $this->createBundle($assets[$type], $type, $bundleName);
                if ($bundleFile) {
                    $bundledAssets[$type][] = [
                        'file' => $bundleFile,
                        'priority' => 50,
                        'bundled' => true
                    ];
                }
            }
        }
        
        return $bundledAssets;
    }
    
    protected function createBundle(array $assets, string $type, string $bundleName): ?string
    {
        $bundleContent = '';
        $bundleHash = '';
        
        foreach ($assets as $asset) {
            $filePath = $this->resolveAssetPath($asset['file']);
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $bundleContent .= $content . "\n";
                $bundleHash .= md5($content);
            }
        }
        
        if (empty($bundleContent)) {
            return null;
        }
        
        // Create bundle filename with hash for cache busting
        $bundleHash = md5($bundleHash);
        $bundleFilename = "{$bundleName}-{$bundleHash}.{$type}";
        $bundleFilePath = $this->bundlePath . '/' . $bundleFilename;
        $bundlePublicPath = $this->publicPath . '/' . $bundleFilename;
        
        // Create bundle file if it doesn't exist
        if (!file_exists($bundleFilePath)) {
            // Minify content
            if ($type === 'css') {
                $bundleContent = $this->minifyCss($bundleContent);
            } elseif ($type === 'js') {
                $bundleContent = $this->minifyJs($bundleContent);
            }
            
            file_put_contents($bundleFilePath, $bundleContent);
        }
        
        return $bundlePublicPath;
    }
    
    protected function minifyCss(string $css): string
    {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace
        $css = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $css);
        
        return $css;
    }
    
    protected function minifyJs(string $js): string
    {
        // Basic JS minification (for production, use a proper minifier)
        $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js); // Remove multi-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js); // Remove single-line comments
        $js = preg_replace('/\s+/', ' ', $js); // Compress whitespace
        
        return trim($js);
    }
}
```

### **2. Caching System:**
```php
class AssetCache
{
    protected $cacheDir;
    protected $ttl;
    
    public function __construct(string $cacheDir, int $ttl = 3600)
    {
        $this->cacheDir = $cacheDir;
        $this->ttl = $ttl;
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }
    
    public function get(string $key): ?array
    {
        $cacheFile = $this->getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            
            if ($cacheData && $cacheData['expires'] > time()) {
                return $cacheData['data'];
            }
            
            // Cache expired, remove file
            unlink($cacheFile);
        }
        
        return null;
    }
    
    public function set(string $key, array $data): void
    {
        $cacheFile = $this->getCacheFile($key);
        $cacheData = [
            'data' => $data,
            'expires' => time() + $this->ttl,
            'created' => time()
        ];
        
        file_put_contents($cacheFile, json_encode($cacheData));
    }
    
    public function invalidate(string $key): void
    {
        $cacheFile = $this->getCacheFile($key);
        
        if (file_exists($cacheFile)) {
            unlink($cacheFile);
        }
    }
    
    public function clear(): void
    {
        $files = glob($this->cacheDir . '/*.cache');
        
        foreach ($files as $file) {
            unlink($file);
        }
    }
    
    protected function getCacheFile(string $key): string
    {
        return $this->cacheDir . '/' . md5($key) . '.cache';
    }
}
```

---

## 🎨 **CUSTOMIZATION GUIDE**

### **1. Custom Template Creation:**
```php
// Create custom template configuration
$customTemplate = [
    'custom_admin' => [
        'default' => [
            'position' => [
                'top' => [
                    'css' => [
                        'vendor/plugins/nodes/bootstrap/dist/css/bootstrap.css',
                        'css/custom-admin.css',
                    ],
                    'js' => [
                        'vendor/plugins/nodes/jquery/dist/jquery.min.js',
                        'vendor/plugins/nodes/bootstrap/dist/js/bootstrap.min.js',
                        'js/custom-admin.js',
                    ],
                ],
            ],
            
            // Custom DataTables configuration
            'datatable' => [
                'js' => [
                    'vendor/DataTables/js/datatables.min.js',
                    'js/custom-datatables.js',
                ],
                'css' => [
                    'vendor/DataTables/css/datatables.css',
                    'css/custom-datatables.css',
                ],
            ],
            
            // Custom components
            'custom_modal' => [
                'js' => ['js/custom-modal.js'],
                'css' => ['css/custom-modal.css'],
            ],
        ],
    ],
];

// Merge with existing configuration
$existingConfig = include 'canvastack.templates.php';
$mergedConfig = array_merge_recursive($existingConfig, $customTemplate);
```

### **2. Environment-Specific Configuration:**
```php
// Environment-based asset loading
class EnvironmentAssetLoader extends AssetLoader
{
    protected $environment;
    
    public function __construct(array $config, string $environment = 'production')
    {
        parent::__construct($config);
        $this->environment = $environment;
    }
    
    protected function loadComponent(string $template, string $component): array
    {
        $assets = parent::loadComponent($template, $component);
        
        // Use different assets based on environment
        if ($this->environment === 'development') {
            $assets = $this->loadDevelopmentAssets($assets);
        } elseif ($this->environment === 'production') {
            $assets = $this->loadProductionAssets($assets);
        }
        
        return $assets;
    }
    
    protected function loadDevelopmentAssets(array $assets): array
    {
        // Use unminified versions in development
        foreach ($assets as $type => $typeAssets) {
            foreach ($typeAssets as &$asset) {
                $asset['file'] = str_replace('.min.', '.', $asset['file']);
            }
        }
        
        return $assets;
    }
    
    protected function loadProductionAssets(array $assets): array
    {
        // Ensure minified versions in production
        foreach ($assets as $type => $typeAssets) {
            foreach ($typeAssets as &$asset) {
                if (strpos($asset['file'], '.min.') === false) {
                    $minified = str_replace('.js', '.min.js', $asset['file']);
                    $minified = str_replace('.css', '.min.css', $minified);
                    
                    // Check if minified version exists
                    if ($this->assetExists($minified)) {
                        $asset['file'] = $minified;
                    }
                }
            }
        }
        
        return $assets;
    }
}
```

### **3. Plugin Asset Integration:**
```php
class PluginAssetManager
{
    protected $plugins = [];
    
    public function registerPlugin(string $name, array $assets): void
    {
        $this->plugins[$name] = $assets;
    }
    
    public function getPluginAssets(array $enabledPlugins): array
    {
        $assets = ['css' => [], 'js' => []];
        
        foreach ($enabledPlugins as $plugin) {
            if (isset($this->plugins[$plugin])) {
                $pluginAssets = $this->plugins[$plugin];
                
                foreach (['css', 'js'] as $type) {
                    if (isset($pluginAssets[$type])) {
                        foreach ($pluginAssets[$type] as $asset) {
                            $assets[$type][] = [
                                'file' => $asset,
                                'plugin' => $plugin,
                                'priority' => 60 // Plugin assets load after core
                            ];
                        }
                    }
                }
            }
        }
        
        return $assets;
    }
}

// Usage example
$pluginManager = new PluginAssetManager();

$pluginManager->registerPlugin('table_export', [
    'js' => ['plugins/table-export/table-export.min.js'],
    'css' => ['plugins/table-export/table-export.min.css']
]);

$pluginManager->registerPlugin('advanced_filters', [
    'js' => ['plugins/advanced-filters/advanced-filters.min.js'],
    'css' => ['plugins/advanced-filters/advanced-filters.min.css']
]);
```

---

## 🌐 **CDN VS LOCAL ASSETS**

### **1. CDN Configuration:**
```php
// CDN asset configuration
$cdnConfig = [
    'admin' => [
        'default' => [
            'datatable' => [
                'js' => [
                    'https://cdn.datatables.net/v/bs4/jszip-2.5.0/dt-1.13.4/af-2.5.3/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/cr-1.6.2/date-1.4.0/fc-4.2.2/fh-3.3.2/kt-2.8.2/r-2.4.1/rg-1.3.1/rr-1.3.3/sc-2.1.1/sb-1.4.2/sp-2.1.2/sl-1.6.2/sr-1.2.2/datatables.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js',
                    'js/datatables/filter.js' // Local custom file
                ],
                'css' => [
                    'https://cdn.datatables.net/v/bs4/jq-3.6.0/jszip-2.5.0/dt-1.13.4/af-2.5.3/b-2.3.6/b-colvis-2.3.6/b-html5-2.3.6/b-print-2.3.6/cr-1.6.2/date-1.4.0/fc-4.2.2/fh-3.3.2/kt-2.8.2/r-2.4.1/rg-1.3.1/rr-1.3.3/sc-2.1.1/sb-1.4.2/sp-2.1.2/sl-1.6.2/sr-1.2.2/datatables.css'
                ]
            ],
        ],
    ],
];
```

### **2. Fallback System:**
```php
class CDNFallbackLoader
{
    protected $cdnAssets;
    protected $localFallbacks;
    
    public function __construct(array $cdnAssets, array $localFallbacks)
    {
        $this->cdnAssets = $cdnAssets;
        $this->localFallbacks = $localFallbacks;
    }
    
    public function generateFallbackScript(array $assets): string
    {
        $script = '<script>';
        
        foreach ($assets['js'] as $asset) {
            if ($this->isCdnAsset($asset['file'])) {
                $fallback = $this->getFallback($asset['file']);
                if ($fallback) {
                    $testObject = $this->getTestObject($asset['file']);
                    $script .= "
                    if (typeof {$testObject} === 'undefined') {
                        document.write('<script src=\"{$fallback}\"><\/script>');
                    }";
                }
            }
        }
        
        $script .= '</script>';
        
        return $script;
    }
    
    protected function isCdnAsset(string $file): bool
    {
        return strpos($file, 'http://') === 0 || strpos($file, 'https://') === 0;
    }
    
    protected function getFallback(string $cdnUrl): ?string
    {
        foreach ($this->localFallbacks as $pattern => $fallback) {
            if (strpos($cdnUrl, $pattern) !== false) {
                return $fallback;
            }
        }
        
        return null;
    }
    
    protected function getTestObject(string $cdnUrl): string
    {
        $testObjects = [
            'jquery' => 'jQuery',
            'bootstrap' => 'bootstrap',
            'datatables' => 'DataTable',
            'chosen' => 'Chosen',
        ];
        
        foreach ($testObjects as $library => $testObject) {
            if (strpos($cdnUrl, $library) !== false) {
                return $testObject;
            }
        }
        
        return 'undefined';
    }
}
```

### **3. Performance Comparison:**
```php
class AssetPerformanceAnalyzer
{
    public function analyzeLoadTimes(array $assets): array
    {
        $results = [];
        
        foreach ($assets as $type => $typeAssets) {
            foreach ($typeAssets as $asset) {
                $startTime = microtime(true);
                
                if ($this->isCdnAsset($asset['file'])) {
                    $loadTime = $this->testCdnLoadTime($asset['file']);
                } else {
                    $loadTime = $this->testLocalLoadTime($asset['file']);
                }
                
                $results[] = [
                    'file' => $asset['file'],
                    'type' => $type,
                    'source' => $this->isCdnAsset($asset['file']) ? 'CDN' : 'Local',
                    'load_time' => $loadTime,
                    'size' => $this->getAssetSize($asset['file'])
                ];
            }
        }
        
        return $results;
    }
    
    protected function testCdnLoadTime(string $url): float
    {
        $startTime = microtime(true);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_exec($ch);
        curl_close($ch);
        
        return microtime(true) - $startTime;
    }
    
    protected function testLocalLoadTime(string $file): float
    {
        $startTime = microtime(true);
        
        $filePath = public_path($file);
        if (file_exists($filePath)) {
            file_get_contents($filePath);
        }
        
        return microtime(true) - $startTime;
    }
}
```

---

## 🐛 **TROUBLESHOOTING**

### **1. Asset Loading Issues:**
```php
class AssetDiagnostics
{
    public function diagnoseAssetIssues(array $assets): array
    {
        $issues = [];
        
        foreach ($assets as $type => $typeAssets) {
            foreach ($typeAssets as $asset) {
                $assetIssues = $this->checkAsset($asset, $type);
                if (!empty($assetIssues)) {
                    $issues[] = [
                        'file' => $asset['file'],
                        'type' => $type,
                        'issues' => $assetIssues
                    ];
                }
            }
        }
        
        return $issues;
    }
    
    protected function checkAsset(array $asset, string $type): array
    {
        $issues = [];
        
        // Check if file exists
        if (!$this->assetExists($asset['file'])) {
            $issues[] = 'File not found';
        }
        
        // Check file size
        $size = $this->getAssetSize($asset['file']);
        if ($size === 0) {
            $issues[] = 'File is empty';
        } elseif ($size > 1024 * 1024) { // 1MB
            $issues[] = 'File is very large (' . round($size / 1024 / 1024, 2) . 'MB)';
        }
        
        // Check for common issues
        if ($type === 'js') {
            $issues = array_merge($issues, $this->checkJavaScriptIssues($asset['file']));
        } elseif ($type === 'css') {
            $issues = array_merge($issues, $this->checkCssIssues($asset['file']));
        }
        
        return $issues;
    }
    
    protected function checkJavaScriptIssues(string $file): array
    {
        $issues = [];
        
        if (!$this->isCdnAsset($file)) {
            $content = file_get_contents(public_path($file));
            
            // Check for syntax errors (basic check)
            if (strpos($content, 'SyntaxError') !== false) {
                $issues[] = 'Potential syntax error detected';
            }
            
            // Check for missing dependencies
            if (strpos($content, 'jQuery') !== false && !$this->hasJQuery()) {
                $issues[] = 'Requires jQuery but jQuery not loaded';
            }
        }
        
        return $issues;
    }
    
    protected function checkCssIssues(string $file): array
    {
        $issues = [];
        
        if (!$this->isCdnAsset($file)) {
            $content = file_get_contents(public_path($file));
            
            // Check for broken imports
            preg_match_all('/@import\s+["\']([^"\']+)["\']/', $content, $imports);
            foreach ($imports[1] as $import) {
                if (!$this->assetExists($import)) {
                    $issues[] = 'Broken import: ' . $import;
                }
            }
        }
        
        return $issues;
    }
}
```

### **2. Debug Tools:**
```javascript
// Client-side asset debugging
var AssetDebugger = {
    checkLoadedAssets: function() {
        var results = {
            css: [],
            js: [],
            missing: []
        };
        
        // Check CSS files
        $('link[rel="stylesheet"]').each(function() {
            var href = $(this).attr('href');
            results.css.push({
                url: href,
                loaded: this.sheet && this.sheet.cssRules
            });
        });
        
        // Check JS files
        $('script[src]').each(function() {
            var src = $(this).attr('src');
            results.js.push({
                url: src,
                loaded: true // If script tag exists, it was loaded
            });
        });
        
        // Check for missing dependencies
        var dependencies = {
            'jQuery': typeof jQuery !== 'undefined',
            'Bootstrap': typeof bootstrap !== 'undefined',
            'DataTables': typeof $.fn.DataTable !== 'undefined',
            'Chosen': typeof $.fn.chosen !== 'undefined'
        };
        
        Object.keys(dependencies).forEach(function(dep) {
            if (!dependencies[dep]) {
                results.missing.push(dep);
            }
        });
        
        console.log('Asset Debug Results:', results);
        return results;
    },
    
    testAssetLoad: function(url) {
        return new Promise(function(resolve, reject) {
            var img = new Image();
            img.onload = function() { resolve(true); };
            img.onerror = function() { reject(false); };
            img.src = url + '?test=' + Date.now();
        });
    }
};

// Auto-run diagnostics in development
if (window.location.hostname === 'localhost') {
    $(document).ready(function() {
        setTimeout(function() {
            AssetDebugger.checkLoadedAssets();
        }, 2000);
    });
}
```

---

*This documentation covers the complete Template System & Asset Management. The system provides flexible, performant, and reliable asset loading with comprehensive customization options and debugging capabilities.*