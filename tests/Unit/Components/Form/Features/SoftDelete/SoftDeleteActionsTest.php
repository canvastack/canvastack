<?php

namespace Tests\Unit\Components\Form\Features\SoftDelete;

use Canvastack\Canvastack\Components\Form\Features\SoftDelete\SoftDeleteActions;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SoftDeleteActions.
 *
 * Requirements: 8.7, 8.8, 8.9
 */
class SoftDeleteActionsTest extends TestCase
{
    protected SoftDeleteActions $actions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actions = new SoftDeleteActions();
    }

    /** @test */
    public function it_renders_restore_button()
    {
        $result = $this->actions->render('App\\Models\\User', 1);

        $this->assertStringContainsString('Restore Record', $result);
        $this->assertStringContainsString('soft-delete-actions', $result);
    }

    /** @test */
    public function it_includes_restore_icon()
    {
        $result = $this->actions->render('App\\Models\\User', 1);

        // Check for SVG restore icon (circular arrows)
        $this->assertStringContainsString('<svg', $result);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $result);
    }

    /** @test */
    public function it_encodes_model_class_in_base64()
    {
        $modelClass = 'App\\Models\\User';
        $result = $this->actions->render($modelClass, 1);

        $encoded = base64_encode($modelClass);
        $this->assertStringContainsString($encoded, $result);
    }

    /** @test */
    public function it_includes_model_id_in_button()
    {
        $result = $this->actions->render('App\\Models\\User', 123);

        $this->assertStringContainsString('123', $result);
    }

    /** @test */
    public function it_does_not_show_permanent_delete_by_default()
    {
        $result = $this->actions->render('App\\Models\\User', 1);

        $this->assertStringNotContainsString('Delete Permanently', $result);
    }

    /** @test */
    public function it_shows_permanent_delete_when_enabled()
    {
        $result = $this->actions->render('App\\Models\\User', 1, 'admin', true);

        $this->assertStringContainsString('Delete Permanently', $result);
    }

    /** @test */
    public function it_uses_success_button_style_for_restore()
    {
        $result = $this->actions->render('App\\Models\\User', 1);

        $this->assertStringContainsString('btn-success', $result);
    }

    /** @test */
    public function it_uses_error_button_style_for_permanent_delete()
    {
        $result = $this->actions->render('App\\Models\\User', 1, 'admin', true);

        $this->assertStringContainsString('btn-error', $result);
    }

    /** @test */
    public function it_uses_admin_button_size_for_admin_context()
    {
        $result = $this->actions->render('App\\Models\\User', 1, 'admin');

        $this->assertStringContainsString('btn-md', $result);
    }

    /** @test */
    public function it_uses_small_button_size_for_public_context()
    {
        $result = $this->actions->render('App\\Models\\User', 1, 'public');

        $this->assertStringContainsString('btn-sm', $result);
    }

    /** @test */
    public function it_includes_onclick_handler_for_restore()
    {
        $result = $this->actions->render('App\\Models\\User', 1);

        $this->assertStringContainsString('onclick="restoreSoftDeletedRecord', $result);
    }

    /** @test */
    public function it_includes_onclick_handler_for_permanent_delete()
    {
        $result = $this->actions->render('App\\Models\\User', 1, 'admin', true);

        $this->assertStringContainsString('onclick="permanentlyDeleteRecord', $result);
    }

    /** @test */
    public function it_renders_javascript_functions()
    {
        $result = $this->actions->renderScript('test-token', '/restore', '/delete');

        $this->assertStringContainsString('function restoreSoftDeletedRecord', $result);
        $this->assertStringContainsString('function permanentlyDeleteRecord', $result);
    }

    /** @test */
    public function it_includes_confirmation_dialog_in_script()
    {
        $result = $this->actions->renderScript('test-token', '/restore', '/delete');

        $this->assertStringContainsString('confirm(', $result);
    }

    /** @test */
    public function it_includes_double_confirmation_for_permanent_delete()
    {
        $result = $this->actions->renderScript('test-token', '/restore', '/delete');

        // Should have multiple confirm() calls for permanent delete
        $confirmCount = substr_count($result, 'confirm(');
        $this->assertGreaterThanOrEqual(2, $confirmCount);
    }

    /** @test */
    public function it_includes_fetch_api_calls_in_script()
    {
        $result = $this->actions->renderScript('test-token', '/restore', '/delete');

        $this->assertStringContainsString('fetch(', $result);
    }

    /** @test */
    public function it_includes_csrf_token_in_script()
    {
        $result = $this->actions->renderScript('test-token', '/restore', '/delete');

        $this->assertStringContainsString('X-CSRF-TOKEN', $result);
    }

    /** @test */
    public function it_includes_error_handling_in_script()
    {
        $result = $this->actions->renderScript('test-token', '/restore', '/delete');

        $this->assertStringContainsString('catch', $result);
        $this->assertStringContainsString('error', $result);
    }

    /** @test */
    public function it_includes_hover_animation_classes()
    {
        $result = $this->actions->render('App\\Models\\User', 1);

        $this->assertStringContainsString('hover:scale-105', $result);
        $this->assertStringContainsString('transition-all', $result);
    }

    /** @test */
    public function it_wraps_buttons_in_flex_container()
    {
        $result = $this->actions->render('App\\Models\\User', 1);

        $this->assertStringContainsString('flex', $result);
        $this->assertStringContainsString('gap-3', $result);
    }
}
