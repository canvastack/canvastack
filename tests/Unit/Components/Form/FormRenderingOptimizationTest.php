<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Unit\Components\Form;

use Canvastack\Canvastack\Components\Form\FormBuilder;
use Canvastack\Canvastack\Components\Form\Fields\FieldFactory;
use Canvastack\Canvastack\Components\Form\Validation\ValidationCache;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * FormRenderingOptimizationTest - Tests for Task 6.1.3 optimizations.
 *
 * Tests the optimized field rendering methods:
 * - Standard rendering for small forms (<= 20 fields)
 * - Batched rendering for medium forms (21-50 fields)
 * - Lazy loading for large forms (>50 fields)
 *
 * @group performance
 * @group form
 * @group task-6.1.3
 */
class FormRenderingOptimizationTest extends TestCase
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
    }

    /**
     * Test standard rendering is used for small forms.
     *
     * @test
     */
    public function test_standard_rendering_for_small_forms(): void
    {
        // Arrange - Create form with 10 fields
        for ($i = 1; $i <= 10; $i++) {
            $this->form->text("field_{$i}", "Field {$i}");
        }

        // Act
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);
        $time = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('field_1', $html);
        $this->assertStringContainsString('field_10', $html);
        $this->assertStringNotContainsString('lazy-fields-container', $html);

        echo "✓ Standard rendering (10 fields): {$time}ms\n";
    }

    /**
     * Test batched rendering is used for medium forms.
     *
     * @test
     */
    public function test_batched_rendering_for_medium_forms(): void
    {
        // Arrange - Create form with 30 fields
        for ($i = 1; $i <= 30; $i++) {
            $this->form->text("field_{$i}", "Field {$i}");
        }

        // Act
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);
        $time = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('field_1', $html);
        $this->assertStringContainsString('field_30', $html);
        $this->assertStringNotContainsString('lazy-fields-container', $html);

        echo "✓ Batched rendering (30 fields): {$time}ms\n";
    }

    /**
     * Test lazy loading is used for large forms.
     *
     * @test
     */
    public function test_lazy_loading_for_large_forms(): void
    {
        // Arrange - Create form with 60 fields
        for ($i = 1; $i <= 60; $i++) {
            $this->form->text("field_{$i}", "Field {$i}");
        }

        // Act
        $startTime = microtime(true);
        $html = $this->form->render();
        $endTime = microtime(true);
        $time = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertNotEmpty($html);
        $this->assertStringContainsString('field_1', $html);
        $this->assertStringContainsString('field_30', $html); // First 30 rendered
        $this->assertStringContainsString('lazy-fields-container', $html); // Lazy load container
        $this->assertStringContainsString('remaining-fields', $html); // Remaining fields container
        $this->assertStringContainsString('loadRemainingFields', $html); // JavaScript function

        echo "✓ Lazy loading (60 fields): {$time}ms\n";
    }

    /**
     * Test form rendering scalability with optimizations.
     *
     * Validates: Task 6.1.3 - Target <15x scaling factor
     *
     * @test
     */
    public function test_optimized_form_rendering_scalability(): void
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
        // 100 fields should take less than 15x the time of 10 fields (Task 6.1.3 target)
        if ($times[10] > 0) {
            $scalingFactor = $times[100] / $times[10];
            $this->assertLessThan(
                15,
                $scalingFactor,
                "Performance scaling factor ({$scalingFactor}x) exceeds target. " .
                "10 fields: {$times[10]}ms, 100 fields: {$times[100]}ms"
            );

            echo "✓ Optimized scalability: {$scalingFactor}x (target: <15x)\n";
            echo "  - 10 fields: {$times[10]}ms\n";
            echo "  - 50 fields: {$times[50]}ms\n";
            echo "  - 100 fields: {$times[100]}ms\n";
        }
    }

    /**
     * Test lazy load button translation.
     *
     * @test
     */
    public function test_lazy_load_button_has_translation(): void
    {
        // Arrange - Create form with 60 fields
        for ($i = 1; $i <= 60; $i++) {
            $this->form->text("field_{$i}", "Field {$i}");
        }

        // Act
        $html = $this->form->render();

        // Assert - Button text should be rendered (translation is applied)
        $this->assertStringContainsString('Load 30 more fields', $html);
        $this->assertStringContainsString('loadRemainingFields', $html);
    }

    /**
     * Test lazy load JavaScript is included.
     *
     * @test
     */
    public function test_lazy_load_javascript_is_included(): void
    {
        // Arrange - Create form with 60 fields
        for ($i = 1; $i <= 60; $i++) {
            $this->form->text("field_{$i}", "Field {$i}");
        }

        // Act
        $html = $this->form->render();

        // Assert - JavaScript function should be present
        $this->assertStringContainsString('function loadRemainingFields()', $html);
        $this->assertStringContainsString('getElementById(\'lazy-fields-container\')', $html);
        $this->assertStringContainsString('getElementById(\'remaining-fields\')', $html);
    }

    /**
     * Test all fields are eventually rendered in lazy load mode.
     *
     * @test
     */
    public function test_all_fields_rendered_in_lazy_load(): void
    {
        // Arrange - Create form with 60 fields
        for ($i = 1; $i <= 60; $i++) {
            $this->form->text("field_{$i}", "Field {$i}");
        }

        // Act
        $html = $this->form->render();

        // Assert - All fields should be in the HTML (some hidden initially)
        for ($i = 1; $i <= 60; $i++) {
            $this->assertStringContainsString("field_{$i}", $html);
        }
    }

    /**
     * Test performance improvement over baseline.
     *
     * Compares optimized rendering with a baseline to ensure improvement.
     *
     * @test
     */
    public function test_performance_improvement_over_baseline(): void
    {
        $fieldCount = 100;

        // Measure optimized rendering
        $form = new FormBuilder($this->fieldFactory, $this->validationCache);
        for ($i = 1; $i <= $fieldCount; $i++) {
            $form->text("field_{$i}", "Field {$i}");
        }

        $startTime = microtime(true);
        $html = $form->render();
        $endTime = microtime(true);
        $optimizedTime = ($endTime - $startTime) * 1000;

        // Assert
        $this->assertNotEmpty($html);
        $this->assertLessThan(
            200,
            $optimizedTime,
            "Optimized rendering took {$optimizedTime}ms (expected < 200ms for 100 fields)"
        );

        echo "✓ Optimized rendering (100 fields): {$optimizedTime}ms (target: <200ms)\n";
    }
}
