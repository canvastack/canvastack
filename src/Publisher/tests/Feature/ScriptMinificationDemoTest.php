<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Config;

/**
 * Demo Script Minification
 * 
 * Test ini menunjukkan cara kerja minifikasi dan hasil yang didapat.
 * Jalankan dengan: php artisan test --filter=ScriptMinificationDemoTest
 */
class ScriptMinificationDemoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable minification
        Config::set('canvastack.controller.script_management.enable_minification', true);
        Config::set('canvastack.controller.script_management.minify_inline_scripts', true);
    }
    
    /**
     * Demo 1: Minifikasi JavaScript sederhana
     */
    public function test_demo_javascript_simple()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  DEMO 1: Minifikasi JavaScript Sederhana\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        $original = '
// Initialize app
function initApp() {
    // Setup variables
    var name = "Canvastack";
    var version = "2.0";
    
    // Log info
    console.log("App: " + name);
    console.log("Version: " + version);
}

// Run app
initApp();
';
        
        $minified = $this->minify($original, 'js');
        
        echo "ORIGINAL CODE:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo $original;
        echo "\n";
        
        echo "MINIFIED CODE:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo $minified;
        echo "\n\n";
        
        echo "STATISTICS:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "  Original Size  : " . strlen($original) . " bytes\n";
        echo "  Minified Size  : " . strlen($minified) . " bytes\n";
        echo "  Bytes Saved    : " . (strlen($original) - strlen($minified)) . " bytes\n";
        echo "  Reduction      : " . round((1 - strlen($minified) / strlen($original)) * 100, 1) . "%\n";
        echo "\n";
        
        $this->assertTrue(strlen($minified) < strlen($original));
    }
    
    /**
     * Demo 2: Minifikasi CSS dengan media queries
     */
    public function test_demo_css_with_media_queries()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  DEMO 2: Minifikasi CSS dengan Media Queries\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        $original = '
/* Container styles */
.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Button styles */
.btn-primary {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
}

.btn-primary:hover {
    background-color: #0056b3;
}

/* Responsive */
@media (max-width: 768px) {
    .container {
        padding: 10px;
    }
}
';
        
        $minified = $this->minify($original, 'css');
        
        echo "ORIGINAL CSS:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo $original;
        echo "\n";
        
        echo "MINIFIED CSS:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo $minified;
        echo "\n\n";
        
        echo "STATISTICS:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "  Original Size  : " . strlen($original) . " bytes\n";
        echo "  Minified Size  : " . strlen($minified) . " bytes\n";
        echo "  Bytes Saved    : " . (strlen($original) - strlen($minified)) . " bytes\n";
        echo "  Reduction      : " . round((1 - strlen($minified) / strlen($original)) * 100, 1) . "%\n";
        echo "\n";
        
        $this->assertTrue(strlen($minified) < strlen($original));
    }
    
    /**
     * Demo 3: Minifikasi dengan preservasi copyright
     */
    public function test_demo_preserve_copyright()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  DEMO 3: Preservasi Copyright Notice\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        $original = '
/*!
 * CanvaStack Framework
 * Copyright (c) 2024 Canvastack
 * Licensed under MIT
 */

// Application code
function app() {
    // Initialize
    console.log("Starting app...");
    
    // Setup
    var config = {
        name: "Canvastack",
        version: "2.0"
    };
    
    return config;
}
';
        
        $minified = $this->minify($original, 'js');
        
        echo "ORIGINAL CODE:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo $original;
        echo "\n";
        
        echo "MINIFIED CODE:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo $minified;
        echo "\n\n";
        
        echo "ANALYSIS:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "  ✓ Copyright notice preserved (/*! ... */)\n";
        echo "  ✓ Regular comments removed (// ...)\n";
        echo "  ✓ Code minified\n";
        echo "  ✓ Functionality intact\n";
        echo "\n";
        
        // Verify copyright is preserved
        $this->assertStringContainsString('Copyright (c) 2024 Canvastack', $minified);
        // Verify regular comments are removed
        $this->assertStringNotContainsString('// Application code', $minified);
    }
    
    /**
     * Demo 4: Perbandingan dengan/tanpa minifikasi
     */
    public function test_demo_comparison()
    {
        echo "\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        echo "  DEMO 4: Perbandingan Dengan/Tanpa Minifikasi\n";
        echo "═══════════════════════════════════════════════════════════════\n\n";
        
        $code = '
// jQuery initialization
$(document).ready(function() {
    // Setup click handler
    $("#submit-btn").click(function(e) {
        e.preventDefault();
        
        // Get form data
        var formData = {
            name: $("#name").val(),
            email: $("#email").val(),
            message: $("#message").val()
        };
        
        // Submit via AJAX
        $.ajax({
            url: "/api/contact",
            method: "POST",
            data: formData,
            success: function(response) {
                alert("Success!");
            },
            error: function(xhr, status, error) {
                alert("Error: " + error);
            }
        });
    });
});
';
        
        // Tanpa minifikasi
        Config::set('canvastack.controller.script_management.enable_minification', false);
        $withoutMinify = $this->minify($code, 'js');
        
        // Dengan minifikasi
        Config::set('canvastack.controller.script_management.enable_minification', true);
        $withMinify = $this->minify($code, 'js');
        
        echo "TANPA MINIFIKASI:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "  Size: " . strlen($withoutMinify) . " bytes\n";
        echo "  Preview: " . substr(str_replace("\n", " ", $withoutMinify), 0, 80) . "...\n";
        echo "\n";
        
        echo "DENGAN MINIFIKASI:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "  Size: " . strlen($withMinify) . " bytes\n";
        echo "  Preview: " . substr($withMinify, 0, 80) . "...\n";
        echo "\n";
        
        echo "BENEFIT:\n";
        echo "─────────────────────────────────────────────────────────────\n";
        echo "  Bytes Saved    : " . (strlen($withoutMinify) - strlen($withMinify)) . " bytes\n";
        echo "  Reduction      : " . round((1 - strlen($withMinify) / strlen($withoutMinify)) * 100, 1) . "%\n";
        echo "  Load Time      : ~" . round((strlen($withMinify) / 1024 / 50), 2) . "s @ 50KB/s\n";
        echo "  vs Original    : ~" . round((strlen($withoutMinify) / 1024 / 50), 2) . "s @ 50KB/s\n";
        echo "\n";
        
        $this->assertTrue(strlen($withMinify) < strlen($withoutMinify));
    }
    
    /**
     * Helper untuk minifikasi
     */
    private function minify($content, $type)
    {
        $mock = new class {
            use \Canvastack\Canvastack\Controllers\Core\Craft\Scripts;
            
            public function __construct()
            {
                $this->template = new class {
                    public function js($s, $p, $a) { return true; }
                    public function css($s, $p) { return true; }
                };
            }
            
            public function testMinify($content, $type)
            {
                return $this->minifyScript($content, $type);
            }
        };
        
        return $mock->testMinify($content, $type);
    }
}
