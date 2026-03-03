<?php

namespace Canvastack\Canvastack\Tests\Property\Components\Form;

use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

/**
 * Property 28: Memory Usage Constraint.
 *
 * **Validates: Requirements 13.17**
 *
 * The FormBuilder SHALL maintain memory usage below 20MB for forms with 100 fields and all features enabled.
 *
 * This property test validates that the FormBuilder maintains acceptable memory usage
 * even with complex forms containing many fields and all features enabled.
 */
class MemoryUsageConstraintPropertyTest extends TestCase
{
    protected FormBuilder $form;

    protected function setUp(): void
    {
        parent::setUp();

        $fieldFactory = new FieldFactory();
        $validationCache = new ValidationCache();
        $this->form = new FormBuilder($fieldFactory, $validationCache);
    }

    /**
     * Property: Memory usage SHALL remain below 20MB for forms with 100 fields and all features.
     *
     * @test
     */
    public function memory_usage_remains_below_20mb_for_100_fields_with_all_features(): void
    {
        // Clear any existing memory
        gc_collect_cycles();

        // Record baseline memory
        $baselineMemory = memory_get_usage(true);

        // Add tabs (10 tabs with 10 fields each)
        for ($tabIndex = 1; $tabIndex <= 10; $tabIndex++) {
            $this->form->openTab("Tab {$tabIndex}");

            for ($fieldIndex = 1; $fieldIndex <= 10; $fieldIndex++) {
                $fieldNumber = ($tabIndex - 1) * 10 + $fieldIndex;

                // Vary field types to test all features
                $fieldType = $fieldNumber % 10;

                switch ($fieldType) {
                    case 0:
                        // Text field with character counter
                        $this->form->text("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->maxLength(100)
                            ->placeholder('Enter text')
                            ->icon('user');
                        break;

                    case 1:
                        // Textarea with CKEditor
                        $this->form->textarea("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->rows(5)
                            ->attribute('ckeditor', true);
                        break;

                    case 2:
                        // Select with searchable
                        $options = [];
                        for ($i = 1; $i <= 50; $i++) {
                            $options[$i] = "Option {$i}";
                        }
                        $this->form->select("field_{$fieldNumber}", "Field {$fieldNumber}", $options)
                            ->searchable();
                        break;

                    case 3:
                        // Switch checkbox
                        $this->form->checkbox("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->attribute('check_type', 'switch');
                        break;

                    case 4:
                        // File upload with image preview
                        $this->form->file("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->attribute('imagepreview', true);
                        break;

                    case 5:
                        // Tags input
                        $this->form->tags("field_{$fieldNumber}", "Field {$fieldNumber}");
                        break;

                    case 6:
                        // Date range picker
                        $this->form->daterange("field_{$fieldNumber}", "Field {$fieldNumber}");
                        break;

                    case 7:
                        // Month picker
                        $this->form->month("field_{$fieldNumber}", "Field {$fieldNumber}");
                        break;

                    case 8:
                        // Radio buttons
                        $this->form->radio("field_{$fieldNumber}", "Field {$fieldNumber}", [
                            '1' => 'Option 1',
                            '2' => 'Option 2',
                            '3' => 'Option 3',
                        ]);
                        break;

                    case 9:
                        // Email field
                        $this->form->email("field_{$fieldNumber}", "Field {$fieldNumber}")
                            ->placeholder('email@example.com');
                        break;
                }
            }

            $this->form->closeTab();
        }

        // Add Ajax sync relationships (5 cascading dropdowns)
        for ($i = 1; $i <= 5; $i++) {
            $sourceField = "source_{$i}";
            $targetField = "target_{$i}";
            $query = 'SELECT id, name FROM test_table WHERE parent_id = ?';

            $this->form->sync($sourceField, $targetField, 'id', 'name', $query);
        }

        // Render the form
        $html = $this->form->render();

        // Force garbage collection
        gc_collect_cycles();

        // Measure memory usage
        $currentMemory = memory_get_usage(true);
        $memoryUsed = $currentMemory - $baselineMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;

        // Assert memory usage is below 20MB
        $this->assertLessThan(
            20,
            $memoryUsedMB,
            sprintf(
                'Memory usage (%.2f MB) exceeds 20MB limit for form with 100 fields and all features',
                $memoryUsedMB
            )
        );

        // Also verify the form was actually created
        $this->assertNotEmpty($html);
    }

    /**
     * Property: Memory usage SHALL scale reasonably with field count.
     *
     * @test
     */
    public function memory_usage_scales_linearly_with_field_count(): void
    {
        // This test verifies that memory doesn't grow exponentially
        // We test with 10 and 100 fields and ensure 100 fields doesn't use 10x+ memory

        gc_collect_cycles();
        $fieldFactory1 = new FieldFactory();
        $validationCache1 = new ValidationCache();
        $form1 = new FormBuilder($fieldFactory1, $validationCache1);

        $baseline1 = memory_get_peak_usage(true);

        for ($i = 1; $i <= 10; $i++) {
            $form1->text("field_{$i}", "Field {$i}")->maxLength(100);
        }

        $html1 = $form1->render();
        $memory10 = memory_get_peak_usage(true) - $baseline1;

        unset($form1, $fieldFactory1, $validationCache1);
        gc_collect_cycles();

        // Now test with 100 fields
        $fieldFactory2 = new FieldFactory();
        $validationCache2 = new ValidationCache();
        $form2 = new FormBuilder($fieldFactory2, $validationCache2);

        $baseline2 = memory_get_peak_usage(true);

        for ($i = 1; $i <= 100; $i++) {
            $form2->text("field_{$i}", "Field {$i}")->maxLength(100);
        }

        $html2 = $form2->render();
        $memory100 = memory_get_peak_usage(true) - $baseline2;

        // Verify forms were created
        $this->assertNotEmpty($html1);
        $this->assertNotEmpty($html2);

        // Memory for 100 fields should be less than 15x memory for 10 fields
        // (allowing for some overhead, but ensuring it's not exponential)
        if ($memory10 > 0) {
            $this->assertLessThan(
                $memory10 * 15,
                $memory100,
                sprintf(
                    'Memory usage grows too fast. 10 fields=%.2f MB, 100 fields=%.2f MB',
                    $memory10 / 1024 / 1024,
                    $memory100 / 1024 / 1024
                )
            );
        } else {
            // If memory10 is 0, just verify memory100 is reasonable (< 10MB)
            $this->assertLessThan(
                10 * 1024 * 1024,
                $memory100,
                sprintf('Memory usage for 100 fields (%.2f MB) is too high', $memory100 / 1024 / 1024)
            );
        }
    }

    /**
     * Property: Memory SHALL be released after form rendering.
     *
     * @test
     */
    public function memory_is_released_after_form_rendering(): void
    {
        gc_collect_cycles();
        $initialMemory = memory_get_usage(true);

        // Create and render multiple forms
        for ($i = 1; $i <= 5; $i++) {
            $fieldFactory = new FieldFactory();
            $validationCache = new ValidationCache();
            $form = new FormBuilder($fieldFactory, $validationCache);

            for ($j = 1; $j <= 20; $j++) {
                $form->text("field_{$j}", "Field {$j}");
            }

            $html = $form->render();
            $this->assertNotEmpty($html);

            // Unset form to allow garbage collection
            unset($form);
        }

        // Force garbage collection
        gc_collect_cycles();

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = ($finalMemory - $initialMemory) / 1024 / 1024;

        // Memory increase should be minimal (< 5MB) after garbage collection
        $this->assertLessThan(
            5,
            $memoryIncrease,
            sprintf(
                'Memory not properly released after form rendering. Increase: %.2f MB',
                $memoryIncrease
            )
        );
    }

    /**
     * Property: Caching SHALL reduce memory usage for repeated renders.
     *
     * @test
     */
    public function caching_reduces_memory_usage_for_repeated_renders(): void
    {
        Cache::flush();

        // First render without cache
        gc_collect_cycles();
        $baselineMemory = memory_get_usage(true);

        $fieldFactory1 = new FieldFactory();
        $validationCache1 = new ValidationCache();
        $form1 = new FormBuilder($fieldFactory1, $validationCache1);

        for ($i = 1; $i <= 50; $i++) {
            $form1->text("field_{$i}", "Field {$i}")
                ->maxLength(100);
        }

        $html1 = $form1->render();

        gc_collect_cycles();
        $memoryAfterFirst = memory_get_usage(true);
        $firstRenderMemory = ($memoryAfterFirst - $baselineMemory) / 1024 / 1024;

        unset($form1);
        gc_collect_cycles();

        // Second render with potential cache hits
        $baselineMemory2 = memory_get_usage(true);

        $fieldFactory2 = new FieldFactory();
        $validationCache2 = new ValidationCache();
        $form2 = new FormBuilder($fieldFactory2, $validationCache2);

        for ($i = 1; $i <= 50; $i++) {
            $form2->text("field_{$i}", "Field {$i}")
                ->maxLength(100);
        }

        $html2 = $form2->render();

        gc_collect_cycles();
        $memoryAfterSecond = memory_get_usage(true);
        $secondRenderMemory = ($memoryAfterSecond - $baselineMemory2) / 1024 / 1024;

        // Both renders should produce output
        $this->assertNotEmpty($html1);
        $this->assertNotEmpty($html2);

        // Second render should use similar or less memory (within 20% variance)
        $this->assertLessThanOrEqual(
            $firstRenderMemory * 1.2,
            $secondRenderMemory,
            sprintf(
                'Second render uses more memory than expected. First: %.2f MB, Second: %.2f MB',
                $firstRenderMemory,
                $secondRenderMemory
            )
        );
    }
}
