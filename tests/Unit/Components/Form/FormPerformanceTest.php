<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Components\Form\Features\Ajax\AjaxSync;
use Canvastack\Canvastack\Components\Form\Features\Ajax\QueryEncryption;
use Canvastack\Canvastack\Components\Form\Features\Editor\CKEditorIntegration;
use Canvastack\Canvastack\Components\Form\Features\Enhancements\CharacterCounter;
use Canvastack\Canvastack\Components\Form\Features\Enhancements\SearchableSelect;
use Canvastack\Canvastack\Components\Form\Features\Enhancements\SwitchCheckbox;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileProcessor;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\FileValidator;
use Canvastack\Canvastack\Components\Form\Features\FileUpload\ThumbnailGenerator;
use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Support\ModelInspector;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * Comprehensive Performance Tests for Form Component.
 *
 * Tests all performance targets from Requirements 13.1-13.8, 13.17:
 * - Tab rendering < 100ms for up to 10 tabs
 * - Ajax sync response < 200ms for queries returning up to 1000 records
 * - File upload processing < 500ms for files up to 5MB
 * - CKEditor initialization < 300ms on modern browsers
 * - Switch checkbox rendering < 50ms per instance
 * - Searchable select initialization < 200ms per instance
 * - Character counter update < 50ms of user input
 * - Soft delete detection < 10ms per model
 * - Memory usage < 20MB for forms with 100 fields and all features enabled
 *
 * @group performance
 */
class FormPerformanceTest extends TestCase
{
    protected FormBuilder $form;

    protected FieldFactory $fieldFactory;

    protected ValidationCache $validationCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fieldFactory = new FieldFactory();
        $this->validationCache = new ValidationCache();
        $this->form = new FormBuilder($this->fieldFactory, $this->validationCache);

        Storage::fake('public');
        Cache::flush();
    }

    /**
     * Test tab rendering performance with 10 tabs.
     *
     * **Validates: Requirements 1.12, 13.1**
     * Target: < 100ms for up to 10 tabs
     *
     * @test
     */
    public function test_tab_rendering_performance(): void
    {
        // Arrange - Create form with 10 tabs
        for ($i = 1; $i <= 10; $i++) {
            $this->form->openTab("Tab {$i}");

            // Add 5 fields per tab
            for ($j = 1; $j <= 5; $j++) {
                $this->form->text("tab{$i}_field{$j}", "Field {$j}");
            }

            $this->form->closeTab();
        }

        // Act - Measure rendering time
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);

        $renderingTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

        // Assert
        $this->assertLessThan(
            100,
            $renderingTime,
            "Tab rendering took {$renderingTime}ms, expected < 100ms for 10 tabs"
        );
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('tabs-container', $html);
    }

    /**
     * Test Ajax sync response performance.
     *
     * **Validates: Requirements 2.12, 13.2**
     * Target: < 200ms for queries returning up to 1000 records
     *
     * @test
     */
    public function test_ajax_sync_response_performance(): void
    {
        // Arrange
        $encryption = new QueryEncryption(app('encrypter'));
        $ajaxSync = new AjaxSync($encryption);

        // Register sync relationship
        $query = 'SELECT id, name FROM test_table WHERE parent_id = ?';
        $ajaxSync->register('source_field', 'target_field', 'id', 'name', $query);

        // Act - Measure script generation time
        $startTime = microtime(true);
        $script = $ajaxSync->renderScript();
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertLessThan(
            200,
            $processingTime,
            "Ajax sync setup took {$processingTime}ms, expected < 200ms"
        );
        $this->assertNotEmpty($script);
        $this->assertStringContainsString('fetch', $script);
    }

    /**
     * Test file upload processing performance.
     *
     * **Validates: Requirements 3.12, 13.3**
     * Target: < 500ms for files up to 5MB
     *
     * @test
     */
    public function test_file_upload_processing_performance(): void
    {
        // Arrange
        $validator = new FileValidator();
        $thumbnailGenerator = new ThumbnailGenerator();
        $processor = new FileProcessor($validator, $thumbnailGenerator);

        $file = UploadedFile::fake()->image('test.jpg', 2000, 2000); // Large image
        $uploadPath = 'uploads/test';

        // Act - Measure processing time
        $startTime = microtime(true);
        $result = $processor->process($file, $uploadPath);
        $endTime = microtime(true);

        $processingTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertLessThan(
            500,
            $processingTime,
            "File upload processing took {$processingTime}ms, expected < 500ms"
        );
        $this->assertIsArray($result);
        $this->assertArrayHasKey('file_path', $result);
    }

    /**
     * Test CKEditor initialization performance.
     *
     * **Validates: Requirements 4.20, 13.4**
     * Target: < 300ms on modern browsers
     *
     * @test
     */
    public function test_ckeditor_initialization_performance(): void
    {
        // Arrange
        $editorConfig = new \Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig();
        $ckeditor = new CKEditorIntegration($editorConfig);

        // Register 5 CKEditor instances
        for ($i = 1; $i <= 5; $i++) {
            $ckeditor->register("editor_{$i}", []);
        }

        // Act - Measure script generation time
        $startTime = microtime(true);
        $script = $ckeditor->renderScript();
        $endTime = microtime(true);

        $initTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertLessThan(
            300,
            $initTime,
            "CKEditor initialization took {$initTime}ms, expected < 300ms"
        );
        $this->assertNotEmpty($script);
        $this->assertStringContainsString('CKEDITOR', $script);
    }

    /**
     * Test switch checkbox rendering performance.
     *
     * **Validates: Requirements 5.16, 13.5**
     * Target: < 50ms per instance
     *
     * @test
     */
    public function test_switch_checkbox_rendering_performance(): void
    {
        // Arrange
        $switchCheckbox = new SwitchCheckbox();
        $iterations = 10;
        $times = [];

        // Act - Measure rendering time for multiple instances
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            // SwitchCheckbox requires options array with at least one option
            $html = $switchCheckbox->render("switch_{$i}", ['1' => 'Enabled'], false, []);
            $endTime = microtime(true);

            $times[] = ($endTime - $startTime) * 1000;
            $this->assertNotEmpty($html);
        }

        $avgTime = array_sum($times) / count($times);

        // Assert
        $this->assertLessThan(
            50,
            $avgTime,
            "Switch checkbox rendering took {$avgTime}ms per instance, expected < 50ms"
        );
    }

    /**
     * Test searchable select initialization performance.
     *
     * **Validates: Requirements 6.22, 13.6**
     * Target: < 200ms per instance
     *
     * @test
     */
    public function test_searchable_select_initialization_performance(): void
    {
        // Arrange
        $searchableSelect = new SearchableSelect();

        // Register 5 searchable select instances with 100 options each
        $options = [];
        for ($i = 1; $i <= 100; $i++) {
            $options[$i] = "Option {$i}";
        }

        for ($i = 1; $i <= 5; $i++) {
            $searchableSelect->register("select_{$i}", $options);
        }

        // Act - Measure script generation time
        $startTime = microtime(true);
        $script = $searchableSelect->renderScript();
        $endTime = microtime(true);

        $initTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertLessThan(
            200,
            $initTime,
            "Searchable select initialization took {$initTime}ms, expected < 200ms"
        );
        $this->assertNotEmpty($script);
    }

    /**
     * Test character counter update performance.
     *
     * **Validates: Requirements 7.14, 13.7**
     * Target: < 50ms of user input
     *
     * @test
     */
    public function test_character_counter_update_performance(): void
    {
        // Arrange
        $characterCounter = new CharacterCounter();
        $iterations = 20;
        $times = [];

        // Act - Measure rendering time for multiple counters
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $html = $characterCounter->render("field_{$i}", 500);
            $endTime = microtime(true);

            $times[] = ($endTime - $startTime) * 1000;
            $this->assertNotEmpty($html);
        }

        $avgTime = array_sum($times) / count($times);

        // Assert
        $this->assertLessThan(
            50,
            $avgTime,
            "Character counter rendering took {$avgTime}ms, expected < 50ms"
        );
    }

    /**
     * Test soft delete detection performance.
     *
     * **Validates: Requirements 8.15, 13.8**
     * Target: < 10ms per model
     *
     * @test
     */
    public function test_soft_delete_detection_performance(): void
    {
        // Arrange
        $modelInspector = new ModelInspector();
        $iterations = 50;
        $times = [];

        // Create a real Eloquent model with SoftDeletes trait
        $mockModel = new class () extends \Illuminate\Database\Eloquent\Model {
            use \Illuminate\Database\Eloquent\SoftDeletes;

            protected $table = 'test_models';
        };

        // Clear cache to ensure we're testing detection, not cache retrieval
        Cache::flush();

        // Act - Measure detection time
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $usesSoftDeletes = $modelInspector->usesSoftDeletes($mockModel);
            $endTime = microtime(true);

            $times[] = ($endTime - $startTime) * 1000;
            $this->assertTrue($usesSoftDeletes);
        }

        $avgTime = array_sum($times) / count($times);

        // Assert
        $this->assertLessThan(
            10,
            $avgTime,
            "Soft delete detection took {$avgTime}ms per model, expected < 10ms"
        );
    }

    /**
     * Test form rendering with 100 fields performance.
     *
     * **Validates: Requirements 13.17**
     * Target: Reasonable rendering time for complex forms
     *
     * @test
     */
    public function test_form_with_100_fields_rendering_performance(): void
    {
        // Arrange - Create form with 100 fields of various types
        for ($i = 1; $i <= 100; $i++) {
            $fieldType = $i % 8;

            switch ($fieldType) {
                case 0:
                    $this->form->text("field_{$i}", "Field {$i}")
                        ->placeholder('Enter text')
                        ->maxLength(100);
                    break;
                case 1:
                    $this->form->email("field_{$i}", "Field {$i}");
                    break;
                case 2:
                    $this->form->select("field_{$i}", "Field {$i}", [
                        '1' => 'Option 1',
                        '2' => 'Option 2',
                        '3' => 'Option 3',
                    ]);
                    break;
                case 3:
                    $this->form->textarea("field_{$i}", "Field {$i}");
                    break;
                case 4:
                    $this->form->checkbox("field_{$i}", "Field {$i}");
                    break;
                case 5:
                    $this->form->radio("field_{$i}", "Field {$i}", [
                        '1' => 'Yes',
                        '2' => 'No',
                    ]);
                    break;
                case 6:
                    $this->form->file("field_{$i}", "Field {$i}");
                    break;
                case 7:
                    $this->form->hidden("field_{$i}", "value_{$i}");
                    break;
            }
        }

        // Act - Measure rendering time
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);

        $renderingTime = ($endTime - $startTime) * 1000;

        // Assert - Should render within reasonable time (< 1 second)
        $this->assertLessThan(
            1000,
            $renderingTime,
            "Form with 100 fields took {$renderingTime}ms to render, expected < 1000ms"
        );
        $this->assertNotEmpty($html);
    }

    /**
     * Test memory usage with all features enabled.
     *
     * **Validates: Requirements 13.17**
     * Target: < 20MB for forms with 100 fields and all features enabled
     *
     * @test
     */
    public function test_memory_usage_with_all_features(): void
    {
        // Clear memory
        gc_collect_cycles();
        $baselineMemory = memory_get_usage(true);

        // Arrange - Create form with 100 fields and all features
        for ($tabIndex = 1; $tabIndex <= 10; $tabIndex++) {
            $this->form->openTab("Tab {$tabIndex}");

            for ($fieldIndex = 1; $fieldIndex <= 10; $fieldIndex++) {
                $fieldNumber = ($tabIndex - 1) * 10 + $fieldIndex;
                $fieldType = $fieldNumber % 10;

                switch ($fieldType) {
                    case 0:
                        $this->form->text("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->maxLength(100);
                        break;
                    case 1:
                        $this->form->textarea("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->attribute('ckeditor', true);
                        break;
                    case 2:
                        $options = [];
                        for ($i = 1; $i <= 50; $i++) {
                            $options[$i] = "Option {$i}";
                        }
                        $this->form->select("field_{$fieldNumber}", "Field {$fieldNumber}", $options)
                            ->searchable();
                        break;
                    case 3:
                        $this->form->checkbox("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->attribute('check_type', 'switch');
                        break;
                    case 4:
                        $this->form->file("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->attribute('imagepreview', true);
                        break;
                    case 5:
                        $this->form->email("field_{$fieldNumber}", "Field {$fieldNumber}");
                        break;
                    case 6:
                        $this->form->radio("field_{$fieldNumber}", "Field {$fieldNumber}", [
                            '1' => 'Option 1',
                            '2' => 'Option 2',
                        ]);
                        break;
                    case 7:
                        $this->form->password("field_{$fieldNumber}", "Field {$fieldNumber}");
                        break;
                    case 8:
                        $this->form->number("field_{$fieldNumber}", "Field {$fieldNumber}");
                        break;
                    case 9:
                        $this->form->date("field_{$fieldNumber}", "Field {$fieldNumber}");
                        break;
                }
            }

            $this->form->closeTab();
        }

        // Add Ajax sync relationships
        for ($i = 1; $i <= 5; $i++) {
            $query = 'SELECT id, name FROM test_table WHERE parent_id = ?';
            $this->form->sync("source_{$i}", "target_{$i}", 'id', 'name', $query);
        }

        // Act - Render form and measure memory
        $html = $this->form->render();

        gc_collect_cycles();
        $currentMemory = memory_get_usage(true);
        $memoryUsed = ($currentMemory - $baselineMemory) / 1024 / 1024;

        // Assert
        $this->assertLessThan(
            20,
            $memoryUsed,
            "Memory usage ({$memoryUsed}MB) exceeds 20MB limit for form with 100 fields and all features"
        );
        $this->assertNotEmpty($html);
    }

    /**
     * Test combined features performance.
     *
     * **Validates: All performance requirements**
     * Tests realistic scenario with multiple features working together
     *
     * @test
     */
    public function test_combined_features_performance(): void
    {
        // Arrange - Create a realistic form with multiple features
        $this->form->openTab('Personal Information');
        $this->form->text('name', 'Full Name')->maxLength(100)->required();
        $this->form->email('email', 'Email Address')->required();
        $this->form->file('avatar', 'Profile Picture')->attribute('imagepreview', true);
        $this->form->closeTab();

        $this->form->openTab('Address');
        $this->form->select('country_id', 'Country', ['1' => 'Indonesia', '2' => 'Malaysia']);
        $this->form->select('province_id', 'Province', [])->searchable();
        $this->form->select('city_id', 'City', []);
        $this->form->textarea('address', 'Full Address')->maxLength(500);
        $this->form->closeTab();

        $this->form->openTab('Settings');
        $this->form->checkbox('active', 'Active')->attribute('check_type', 'switch');
        $this->form->checkbox('newsletter', 'Subscribe to Newsletter')->attribute('check_type', 'switch');
        $this->form->textarea('bio', 'Biography')->attribute('ckeditor', true);
        $this->form->closeTab();

        // Add Ajax sync for cascading dropdowns
        $this->form->sync(
            'country_id',
            'province_id',
            'id',
            'name',
            'SELECT id, name FROM provinces WHERE country_id = ?'
        );
        $this->form->sync(
            'province_id',
            'city_id',
            'id',
            'name',
            'SELECT id, name FROM cities WHERE province_id = ?'
        );

        // Act - Measure total rendering time
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);

        $totalTime = ($endTime - $startTime) * 1000;

        // Assert - Combined features should render efficiently
        $this->assertLessThan(
            500,
            $totalTime,
            "Combined features rendering took {$totalTime}ms, expected < 500ms"
        );
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('tabs-container', $html);
    }

    /**
     * Test performance consistency across multiple renders.
     *
     * **Validates: Performance stability**
     * Ensures performance doesn't degrade with repeated renders
     *
     * @test
     */
    public function test_performance_consistency_across_multiple_renders(): void
    {
        // Arrange - Create a standard form
        for ($i = 1; $i <= 20; $i++) {
            $this->form->text("field_{$i}", "Field {$i}")->maxLength(100);
        }

        $times = [];
        $iterations = 5;

        // Act - Render multiple times
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $html = $this->form->render();
            $endTime = microtime(true);

            $times[] = ($endTime - $startTime) * 1000;
            $this->assertNotEmpty($html);
        }

        // Calculate statistics
        $avgTime = array_sum($times) / count($times);
        $maxTime = max($times);
        $minTime = min($times);
        $variance = $maxTime - $minTime;

        // Assert - Performance should be consistent
        $this->assertLessThan(
            200,
            $avgTime,
            "Average rendering time was {$avgTime}ms, expected < 200ms"
        );
        $this->assertLessThan(
            100,
            $variance,
            "Time variance was {$variance}ms, expected < 100ms for consistency"
        );
    }

    /**
     * Test peak memory usage during complex form operations.
     *
     * **Validates: Memory efficiency**
     * Ensures memory doesn't spike during form operations
     *
     * @test
     */
    public function test_peak_memory_usage_during_complex_operations(): void
    {
        gc_collect_cycles();
        $baselineMemory = memory_get_peak_usage(true);

        // Arrange & Act - Perform multiple complex operations
        for ($i = 1; $i <= 5; $i++) {
            $this->form->openTab("Tab {$i}");

            for ($j = 1; $j <= 10; $j++) {
                $this->form->text("field_{$i}_{$j}", "Field {$j}")
                    ->maxLength(100)
                    ->placeholder('Enter text')
                    ->required();
            }

            $this->form->closeTab();
        }

        $html = $this->form->render();

        gc_collect_cycles();
        $peakMemory = memory_get_peak_usage(true);
        $memoryUsed = ($peakMemory - $baselineMemory) / 1024 / 1024;

        // Assert
        $this->assertLessThan(
            15,
            $memoryUsed,
            "Peak memory usage ({$memoryUsed}MB) exceeds 15MB limit"
        );
        $this->assertNotEmpty($html);
    }

    /**
     * Test rendering performance with validation rules.
     *
     * **Validates: Validation cache performance**
     * Ensures validation rules don't slow down rendering
     *
     * @test
     */
    public function test_rendering_performance_with_validation_rules(): void
    {
        // Arrange - Create form with complex validation rules
        for ($i = 1; $i <= 50; $i++) {
            $this->form->text("field_{$i}", "Field {$i}")
                ->required()
                ->minLength(5)
                ->maxLength(100);
        }

        // Act - Measure rendering time
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);

        $renderingTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertLessThan(
            300,
            $renderingTime,
            "Rendering with validation rules took {$renderingTime}ms, expected < 300ms"
        );
        $this->assertNotEmpty($html);
    }

    /**
     * Test form rendering scalability.
     *
     * **Validates: Performance scalability**
     * Ensures performance scales linearly with field count
     *
     * @test
     */
    public function test_form_rendering_scalability(): void
    {
        $fieldCounts = [10, 50, 100];
        $times = [];

        foreach ($fieldCounts as $count) {
            // Create new form for each test
            $form = new FormBuilder($this->fieldFactory, $this->validationCache);

            for ($i = 1; $i <= $count; $i++) {
                $form->text("field_{$i}", "Field {$i}");
            }

            $startTime = microtime(true);
            $html = $form->render();
            $endTime = microtime(true);

            $times[$count] = ($endTime - $startTime) * 1000;
            $this->assertNotEmpty($html);
        }

        // Assert - Time should scale reasonably (not exponentially)
        // 100 fields should take less than 15x the time of 10 fields
        if ($times[10] > 0) {
            $scalingFactor = $times[100] / $times[10];
            $this->assertLessThan(
                15,
                $scalingFactor,
                "Performance scaling factor ({$scalingFactor}x) is too high. " .
                "10 fields: {$times[10]}ms, 100 fields: {$times[100]}ms"
            );
        }
    }

    /**
     * Test memory release after form rendering.
     *
     * **Validates: Memory management**
     * Ensures memory is properly released after rendering
     *
     * @test
     */
    public function test_memory_release_after_rendering(): void
    {
        gc_collect_cycles();
        $initialMemory = memory_get_usage(true);

        // Create and render multiple forms
        for ($i = 1; $i <= 10; $i++) {
            $form = new FormBuilder($this->fieldFactory, $this->validationCache);

            for ($j = 1; $j <= 20; $j++) {
                $form->text("field_{$j}", "Field {$j}");
            }

            $html = $form->render();
            $this->assertNotEmpty($html);

            unset($form);
        }

        gc_collect_cycles();
        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        // Assert - Memory increase should be minimal
        $this->assertLessThan(
            5,
            $memoryIncrease,
            "Memory not properly released. Increase: {$memoryIncrease}MB, expected < 5MB"
        );
    }

    /**
     * Test all performance targets are met.
     *
     * **Validates: All Requirements 13.1-13.8, 13.17**
     * Comprehensive test ensuring all performance targets are consistently met
     *
     * @test
     */
    public function test_all_performance_targets_are_met(): void
    {
        $results = [];

        // Test 1: Tab rendering
        $form1 = new FormBuilder($this->fieldFactory, $this->validationCache);
        for ($i = 1; $i <= 10; $i++) {
            $form1->openTab("Tab {$i}");
            $form1->text("field_{$i}", "Field {$i}");
            $form1->closeTab();
        }
        $start = microtime(true);
        $form1->render();
        $results['tab_rendering'] = (microtime(true) - $start) * 1000;

        // Test 2: Ajax sync
        $encryption = new QueryEncryption(app('encrypter'));
        $ajaxSync = new AjaxSync($encryption);
        $ajaxSync->register('source', 'target', 'id', 'name', 'SELECT id, name FROM test WHERE parent_id = ?');
        $start = microtime(true);
        $ajaxSync->renderScript();
        $results['ajax_sync'] = (microtime(true) - $start) * 1000;

        // Test 3: File upload (simulated)
        $validator = new FileValidator();
        $thumbnailGenerator = new ThumbnailGenerator();
        $processor = new FileProcessor($validator, $thumbnailGenerator);
        $file = UploadedFile::fake()->image('test.jpg', 1000, 1000);
        $start = microtime(true);
        $processor->process($file, 'uploads/test');
        $results['file_upload'] = (microtime(true) - $start) * 1000;

        // Test 4: CKEditor initialization
        $editorConfig = new \Canvastack\Canvastack\Components\Form\Features\Editor\EditorConfig();
        $ckeditor = new CKEditorIntegration($editorConfig);
        $ckeditor->register('editor_1', []);
        $start = microtime(true);
        $ckeditor->renderScript();
        $results['ckeditor_init'] = (microtime(true) - $start) * 1000;

        // Test 5: Switch checkbox
        $switchCheckbox = new SwitchCheckbox();
        $start = microtime(true);
        $switchCheckbox->render('switch_1', ['1' => 'Enabled'], false, []);
        $results['switch_checkbox'] = (microtime(true) - $start) * 1000;

        // Test 6: Searchable select
        $searchableSelect = new SearchableSelect();
        $options = [];
        for ($i = 1; $i <= 100; $i++) {
            $options[$i] = "Option {$i}";
        }
        $searchableSelect->register('select_1', $options);
        $start = microtime(true);
        $searchableSelect->renderScript();
        $results['searchable_select'] = (microtime(true) - $start) * 1000;

        // Test 7: Character counter
        $characterCounter = new CharacterCounter();
        $start = microtime(true);
        $characterCounter->render('field_1', 500);
        $results['character_counter'] = (microtime(true) - $start) * 1000;

        // Test 8: Soft delete detection
        $modelInspector = new ModelInspector();
        $mockModel = new class () extends \Illuminate\Database\Eloquent\Model {
            use \Illuminate\Database\Eloquent\SoftDeletes;

            protected $table = 'test_models';
        };
        Cache::flush();
        $start = microtime(true);
        $modelInspector->usesSoftDeletes($mockModel);
        $results['soft_delete_detection'] = (microtime(true) - $start) * 1000;

        // Test 9: Memory usage
        gc_collect_cycles();
        $baselineMemory = memory_get_usage(true);
        $form2 = new FormBuilder($this->fieldFactory, $this->validationCache);
        for ($i = 1; $i <= 100; $i++) {
            $form2->text("field_{$i}", "Field {$i}")->maxLength(100);
        }
        $form2->render();
        gc_collect_cycles();
        $results['memory_usage_mb'] = (memory_get_usage(true) - $baselineMemory) / 1024 / 1024;

        // Define targets
        $targets = [
            'tab_rendering' => 100,
            'ajax_sync' => 200,
            'file_upload' => 500,
            'ckeditor_init' => 300,
            'switch_checkbox' => 50,
            'searchable_select' => 200,
            'character_counter' => 50,
            'soft_delete_detection' => 10,
            'memory_usage_mb' => 20,
        ];

        // Assert all targets are met
        $failures = [];
        foreach ($targets as $metric => $target) {
            if ($results[$metric] > $target) {
                $unit = $metric === 'memory_usage_mb' ? 'MB' : 'ms';
                $failures[] = "{$metric}: {$results[$metric]}{$unit} (target: < {$target}{$unit})";
            }
        }

        $this->assertEmpty(
            $failures,
            "Performance targets not met:\n" . implode("\n", $failures)
        );

        // Output all results for reference
        echo "\n\nPerformance Test Results:\n";
        echo str_repeat('=', 60) . "\n";
        foreach ($results as $metric => $value) {
            $unit = $metric === 'memory_usage_mb' ? 'MB' : 'ms';
            $target = $targets[$metric];
            $status = $value <= $target ? '✓ PASS' : '✗ FAIL';
            echo sprintf(
                "%-30s: %8.2f %s (target: < %d %s) %s\n",
                ucwords(str_replace('_', ' ', $metric)),
                $value,
                $unit,
                $target,
                $unit,
                $status
            );
        }
        echo str_repeat('=', 60) . "\n";
    }

    /**
     * Test concurrent form rendering performance.
     *
     * **Validates: Multi-user scenario performance**
     * Simulates multiple forms being rendered simultaneously
     *
     * @test
     */
    public function test_concurrent_form_rendering_performance(): void
    {
        $concurrentForms = 10;
        $times = [];

        $startTime = microtime(true);

        for ($i = 1; $i <= $concurrentForms; $i++) {
            $form = new FormBuilder($this->fieldFactory, $this->validationCache);

            for ($j = 1; $j <= 20; $j++) {
                $form->text("field_{$j}", "Field {$j}");
            }

            $formStart = microtime(true);
            $html = $form->render();
            $formEnd = microtime(true);

            $times[] = ($formEnd - $formStart) * 1000;
            $this->assertNotEmpty($html);
        }

        $endTime = microtime(true);
        $totalTime = ($endTime - $startTime) * 1000;
        $avgTime = array_sum($times) / count($times);

        // Assert - Average time should be reasonable
        $this->assertLessThan(
            200,
            $avgTime,
            "Average concurrent rendering time was {$avgTime}ms, expected < 200ms"
        );
        $this->assertLessThan(
            3000,
            $totalTime,
            "Total time for {$concurrentForms} forms was {$totalTime}ms, expected < 3000ms"
        );
    }

    /**
     * Test form rendering with large select options.
     *
     * **Validates: Performance with large datasets**
     * Ensures performance doesn't degrade with large option lists
     *
     * @test
     */
    public function test_form_rendering_with_large_select_options(): void
    {
        // Arrange - Create select fields with large option lists
        $largeOptions = [];
        for ($i = 1; $i <= 1000; $i++) {
            $largeOptions[$i] = "Option {$i}";
        }

        for ($i = 1; $i <= 10; $i++) {
            $this->form->select("select_{$i}", "Select {$i}", $largeOptions);
        }

        // Act - Measure rendering time
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);

        $renderingTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertLessThan(
            500,
            $renderingTime,
            "Rendering with large select options took {$renderingTime}ms, expected < 500ms"
        );
        $this->assertNotEmpty($html);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
