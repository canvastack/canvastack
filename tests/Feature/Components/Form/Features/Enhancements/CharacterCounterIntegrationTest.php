<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Tests\Feature\Components\Form\Features\Enhancements;

use Canvastack\Canvastack\Components\Form\Fields\TextareaField;
use Canvastack\Canvastack\Components\Form\Fields\TextField;
use Canvastack\Canvastack\Components\Form\Renderers\AdminRenderer;
use Canvastack\Canvastack\Components\Form\Renderers\PublicRenderer;
use Canvastack\Canvastack\Tests\TestCase;

/**
 * Integration Test: Character Counter with TextField and TextareaField.
 *
 * Tests the integration of CharacterCounter with text fields in both
 * Admin and Public rendering contexts.
 */
class CharacterCounterIntegrationTest extends TestCase
{
    /**
     * Test character counter is rendered for TextField with maxLength in admin context.
     *
     * @test
     */
    public function character_counter_rendered_for_text_field_admin(): void
    {
        $field = new TextField('username', 'Username');
        $field->maxLength(50);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should contain the input field
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="username"', $html);
        $this->assertStringContainsString('maxlength="50"', $html);

        // Should contain character counter
        $this->assertStringContainsString('id="counter-username"', $html);
        $this->assertStringContainsString('<span class="current-count">0</span>', $html);
        $this->assertStringContainsString('<span class="max-count">50</span>', $html);
        $this->assertStringContainsString('characters', $html);

        // Should contain JavaScript
        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('updateCount', $html);

        // Should use admin styling
        $this->assertStringContainsString('text-gray-500', $html);
    }

    /**
     * Test character counter is rendered for TextField with maxLength in public context.
     *
     * @test
     */
    public function character_counter_rendered_for_text_field_public(): void
    {
        $field = new TextField('email', 'Email');
        $field->maxLength(100);

        $renderer = new PublicRenderer();
        $html = $renderer->render($field);

        // Should contain the input field
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('maxlength="100"', $html);

        // Should contain character counter
        $this->assertStringContainsString('id="counter-email"', $html);
        $this->assertStringContainsString('<span class="max-count">100</span>', $html);

        // Should use public styling
        $this->assertStringContainsString('text-gray-600', $html);
    }

    /**
     * Test character counter is rendered for TextareaField with maxLength.
     *
     * @test
     */
    public function character_counter_rendered_for_textarea_field(): void
    {
        $field = new TextareaField('description', 'Description');
        $field->maxLength(500);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should contain the textarea
        $this->assertStringContainsString('<textarea', $html);
        $this->assertStringContainsString('name="description"', $html);
        $this->assertStringContainsString('maxlength="500"', $html);

        // Should contain character counter
        $this->assertStringContainsString('id="counter-description"', $html);
        $this->assertStringContainsString('<span class="max-count">500</span>', $html);
    }

    /**
     * Test character counter is NOT rendered for TextField without maxLength.
     *
     * @test
     */
    public function character_counter_not_rendered_without_max_length(): void
    {
        $field = new TextField('name', 'Name');
        // No maxLength set

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should contain the input field
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="name"', $html);

        // Should NOT contain character counter
        $this->assertStringNotContainsString('id="counter-name"', $html);
        $this->assertStringNotContainsString('current-count', $html);
        $this->assertStringNotContainsString('max-count', $html);
    }

    /**
     * Test character counter with field name containing special characters.
     *
     * @test
     */
    public function character_counter_handles_special_characters_in_field_name(): void
    {
        $field = new TextField('user[name]', 'User Name');
        $field->maxLength(100);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Counter ID should be sanitized
        $this->assertStringContainsString('id="counter-user_name_"', $html);

        // JavaScript should reference correct field name
        $this->assertStringContainsString("querySelector('[name=\"user[name]\"]')", $html);
    }

    /**
     * Test character counter with multiple fields on same form.
     *
     * @test
     */
    public function character_counter_works_with_multiple_fields(): void
    {
        $field1 = new TextField('title', 'Title');
        $field1->maxLength(100);

        $field2 = new TextareaField('content', 'Content');
        $field2->maxLength(1000);

        $renderer = new AdminRenderer();
        $html1 = $renderer->render($field1);
        $html2 = $renderer->render($field2);

        // Each field should have its own counter
        $this->assertStringContainsString('id="counter-title"', $html1);
        $this->assertStringContainsString('<span class="max-count">100</span>', $html1);

        $this->assertStringContainsString('id="counter-content"', $html2);
        $this->assertStringContainsString('<span class="max-count">1000</span>', $html2);

        // Each should have its own JavaScript
        $this->assertStringContainsString("querySelector('[name=\"title\"]')", $html1);
        $this->assertStringContainsString("querySelector('[name=\"content\"]')", $html2);
    }

    /**
     * Test character counter includes ARIA attributes for accessibility.
     *
     * @test
     */
    public function character_counter_includes_aria_attributes(): void
    {
        $field = new TextField('bio', 'Bio');
        $field->maxLength(200);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should have ARIA attributes
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);
        $this->assertStringContainsString('aria-atomic="true"', $html);
    }

    /**
     * Test character counter includes dark mode classes.
     *
     * @test
     */
    public function character_counter_includes_dark_mode_classes(): void
    {
        $field = new TextField('comment', 'Comment');
        $field->maxLength(300);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should have dark mode classes
        $this->assertStringContainsString('dark:text-gray-400', $html);
    }

    /**
     * Test character counter JavaScript includes color change logic.
     *
     * @test
     */
    public function character_counter_javascript_includes_color_logic(): void
    {
        $field = new TextareaField('message', 'Message');
        $field->maxLength(500);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should have percentage calculation
        $this->assertStringContainsString('percentage', $html);

        // Should have color change thresholds
        $this->assertStringContainsString('percentage >= 100', $html);
        $this->assertStringContainsString('percentage >= 90', $html);

        // Should have color classes
        $this->assertStringContainsString('text-red-500', $html);
        $this->assertStringContainsString('text-amber-500', $html);
    }

    /**
     * Test character counter JavaScript uses Unicode-aware counting.
     *
     * @test
     */
    public function character_counter_javascript_uses_unicode_counting(): void
    {
        $field = new TextField('text', 'Text');
        $field->maxLength(100);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should use spread operator for Unicode-aware counting
        $this->assertStringContainsString('[...field.value].length', $html);
    }

    /**
     * Test character counter JavaScript is wrapped in IIFE.
     *
     * @test
     */
    public function character_counter_javascript_wrapped_in_iife(): void
    {
        $field = new TextField('input', 'Input');
        $field->maxLength(50);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should be wrapped in IIFE
        $this->assertStringContainsString('(function() {', $html);
        $this->assertStringContainsString('})();', $html);

        // Should wait for DOM ready
        $this->assertStringContainsString('DOMContentLoaded', $html);
    }

    /**
     * Test character counter with fluent interface.
     *
     * @test
     */
    public function character_counter_works_with_fluent_interface(): void
    {
        $field = (new TextField('username', 'Username'))
            ->maxLength(50)
            ->placeholder('Enter username')
            ->required();

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        // Should have all attributes
        $this->assertStringContainsString('maxlength="50"', $html);
        $this->assertStringContainsString('placeholder="Enter username"', $html);
        $this->assertStringContainsString('required', $html);

        // Should have character counter
        $this->assertStringContainsString('id="counter-username"', $html);
        $this->assertStringContainsString('<span class="max-count">50</span>', $html);
    }

    /**
     * Test character counter with different maxLength values.
     *
     * @test
     * @dataProvider maxLengthProvider
     */
    public function character_counter_displays_correct_max_length(int $maxLength): void
    {
        $field = new TextField('field', 'Field');
        $field->maxLength($maxLength);

        $renderer = new AdminRenderer();
        $html = $renderer->render($field);

        $this->assertStringContainsString("maxlength=\"{$maxLength}\"", $html);
        $this->assertStringContainsString("<span class=\"max-count\">{$maxLength}</span>", $html);
        $this->assertStringContainsString("const maxLength = {$maxLength};", $html);
    }

    /**
     * Data provider for maxLength values.
     *
     * @return array<array<int>>
     */
    public static function maxLengthProvider(): array
    {
        return [
            [50],
            [100],
            [255],
            [500],
            [1000],
        ];
    }
}
