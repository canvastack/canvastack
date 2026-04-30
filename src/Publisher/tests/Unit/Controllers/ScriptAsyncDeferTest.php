<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;

/**
 * Test async/defer loading support for scripts
 */
class ScriptAsyncDeferTest extends TestCase
{
    /**
     * Test that async attribute is added to external scripts
     */
    public function test_async_attribute_added_to_external_script()
    {
        // Create a mock controller with Scripts trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct() {
                // Mock template property
                $this->template = new class {
                    public $scripts = [];
                    
                    public function js($script, $position, $asCode, $loadMode) {
                        $scriptObj = $this->createScriptObject($script, $asCode, $loadMode);
                        $this->scripts['js'][$position][] = $scriptObj;
                        return $this->scripts;
                    }
                    
                    private function createScriptObject($script, $asCode, $loadMode) {
                        if ($asCode) {
                            return (object)[
                                'url' => false,
                                'html' => '<script type="text/javascript">' . $script . '</script>'
                            ];
                        }
                        
                        $loadModeAttr = '';
                        if (in_array($loadMode, ['async', 'defer'])) {
                            $loadModeAttr = ' ' . $loadMode;
                        }
                        
                        return (object)[
                            'url' => $script,
                            'html' => '<script type="text/javascript" src="' . $script . '"' . $loadModeAttr . '></script>'
                        ];
                    }
                };
            }
        };
        
        // Add script with async
        $controller->js('assets/js/analytics.js', 'bottom', false, 'async');
        
        // Verify async attribute is present
        $scripts = $controller->template->scripts['js']['bottom'];
        $this->assertCount(1, $scripts);
        $this->assertStringContainsString('async', $scripts[0]->html);
        $this->assertStringContainsString('assets/js/analytics.js', $scripts[0]->html);
    }
    
    /**
     * Test that defer attribute is added to external scripts
     */
    public function test_defer_attribute_added_to_external_script()
    {
        // Create a mock controller with Scripts trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct() {
                // Mock template property
                $this->template = new class {
                    public $scripts = [];
                    
                    public function js($script, $position, $asCode, $loadMode) {
                        $scriptObj = $this->createScriptObject($script, $asCode, $loadMode);
                        $this->scripts['js'][$position][] = $scriptObj;
                        return $this->scripts;
                    }
                    
                    private function createScriptObject($script, $asCode, $loadMode) {
                        if ($asCode) {
                            return (object)[
                                'url' => false,
                                'html' => '<script type="text/javascript">' . $script . '</script>'
                            ];
                        }
                        
                        $loadModeAttr = '';
                        if (in_array($loadMode, ['async', 'defer'])) {
                            $loadModeAttr = ' ' . $loadMode;
                        }
                        
                        return (object)[
                            'url' => $script,
                            'html' => '<script type="text/javascript" src="' . $script . '"' . $loadModeAttr . '></script>'
                        ];
                    }
                };
            }
        };
        
        // Add script with defer
        $controller->js('assets/js/app.js', 'bottom', false, 'defer');
        
        // Verify defer attribute is present
        $scripts = $controller->template->scripts['js']['bottom'];
        $this->assertCount(1, $scripts);
        $this->assertStringContainsString('defer', $scripts[0]->html);
        $this->assertStringContainsString('assets/js/app.js', $scripts[0]->html);
    }
    
    /**
     * Test that inline scripts do not get async/defer attributes
     */
    public function test_inline_scripts_do_not_get_async_defer()
    {
        // Create a mock controller with Scripts trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct() {
                // Mock template property
                $this->template = new class {
                    public $scripts = [];
                    
                    public function js($script, $position, $asCode, $loadMode) {
                        $scriptObj = $this->createScriptObject($script, $asCode, $loadMode);
                        $this->scripts['js'][$position][] = $scriptObj;
                        return $this->scripts;
                    }
                    
                    private function createScriptObject($script, $asCode, $loadMode) {
                        if ($asCode) {
                            return (object)[
                                'url' => false,
                                'html' => '<script type="text/javascript">' . $script . '</script>'
                            ];
                        }
                        
                        $loadModeAttr = '';
                        if (in_array($loadMode, ['async', 'defer'])) {
                            $loadModeAttr = ' ' . $loadMode;
                        }
                        
                        return (object)[
                            'url' => $script,
                            'html' => '<script type="text/javascript" src="' . $script . '"' . $loadModeAttr . '></script>'
                        ];
                    }
                };
            }
        };
        
        // Add inline script with async (should be ignored)
        $controller->js('console.log("test");', 'bottom', true, 'async');
        
        // Verify async attribute is NOT present
        $scripts = $controller->template->scripts['js']['bottom'];
        $this->assertCount(1, $scripts);
        $this->assertStringNotContainsString('async', $scripts[0]->html);
        $this->assertStringContainsString('console.log("test");', $scripts[0]->html);
    }
    
    /**
     * Test that null loadMode results in no async/defer attributes
     */
    public function test_null_load_mode_no_attributes()
    {
        // Create a mock controller with Scripts trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct() {
                // Mock template property
                $this->template = new class {
                    public $scripts = [];
                    
                    public function js($script, $position, $asCode, $loadMode) {
                        $scriptObj = $this->createScriptObject($script, $asCode, $loadMode);
                        $this->scripts['js'][$position][] = $scriptObj;
                        return $this->scripts;
                    }
                    
                    private function createScriptObject($script, $asCode, $loadMode) {
                        if ($asCode) {
                            return (object)[
                                'url' => false,
                                'html' => '<script type="text/javascript">' . $script . '</script>'
                            ];
                        }
                        
                        $loadModeAttr = '';
                        if (in_array($loadMode, ['async', 'defer'])) {
                            $loadModeAttr = ' ' . $loadMode;
                        }
                        
                        return (object)[
                            'url' => $script,
                            'html' => '<script type="text/javascript" src="' . $script . '"' . $loadModeAttr . '></script>'
                        ];
                    }
                };
            }
        };
        
        // Add script without loadMode
        $controller->js('assets/js/normal.js', 'bottom', false, null);
        
        // Verify no async/defer attributes
        $scripts = $controller->template->scripts['js']['bottom'];
        $this->assertCount(1, $scripts);
        $this->assertStringNotContainsString('async', $scripts[0]->html);
        $this->assertStringNotContainsString('defer', $scripts[0]->html);
        $this->assertStringContainsString('assets/js/normal.js', $scripts[0]->html);
    }
    
    /**
     * Test backward compatibility - default behavior without loadMode parameter
     */
    public function test_backward_compatibility_without_load_mode()
    {
        // Create a mock controller with Scripts trait
        $controller = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct() {
                // Mock template property
                $this->template = new class {
                    public $scripts = [];
                    
                    public function js($script, $position, $asCode, $loadMode = null) {
                        $scriptObj = $this->createScriptObject($script, $asCode, $loadMode);
                        $this->scripts['js'][$position][] = $scriptObj;
                        return $this->scripts;
                    }
                    
                    private function createScriptObject($script, $asCode, $loadMode) {
                        if ($asCode) {
                            return (object)[
                                'url' => false,
                                'html' => '<script type="text/javascript">' . $script . '</script>'
                            ];
                        }
                        
                        $loadModeAttr = '';
                        if (in_array($loadMode, ['async', 'defer'])) {
                            $loadModeAttr = ' ' . $loadMode;
                        }
                        
                        return (object)[
                            'url' => $script,
                            'html' => '<script type="text/javascript" src="' . $script . '"' . $loadModeAttr . '></script>'
                        ];
                    }
                };
            }
        };
        
        // Add script using old API (without loadMode parameter)
        $controller->js('assets/js/legacy.js', 'bottom', false);
        
        // Verify it works as before (no async/defer)
        $scripts = $controller->template->scripts['js']['bottom'];
        $this->assertCount(1, $scripts);
        $this->assertStringNotContainsString('async', $scripts[0]->html);
        $this->assertStringNotContainsString('defer', $scripts[0]->html);
        $this->assertStringContainsString('assets/js/legacy.js', $scripts[0]->html);
    }
}
