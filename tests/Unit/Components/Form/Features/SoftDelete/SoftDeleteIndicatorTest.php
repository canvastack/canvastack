<?php

namespace Tests\Unit\Components\Form\Features\SoftDelete;

use Canvastack\Canvastack\Components\Form\Features\SoftDelete\SoftDeleteIndicator;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SoftDeleteIndicator.
 *
 * Requirements: 8.5, 8.6
 */
class SoftDeleteIndicatorTest extends TestCase
{
    protected SoftDeleteIndicator $indicator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->indicator = new SoftDeleteIndicator();
    }

    /** @test */
    public function it_returns_empty_string_when_no_deleted_at_timestamp()
    {
        $result = $this->indicator->render(null);

        $this->assertSame('', $result);
    }

    /** @test */
    public function it_renders_indicator_with_badge_and_timestamp()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        $this->assertStringContainsString('soft-delete-indicator', $result);
        $this->assertStringContainsString('This record has been deleted', $result);
        $this->assertStringContainsString('Deleted:', $result);
    }

    /** @test */
    public function it_includes_warning_icon_in_badge()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        // Check for SVG warning icon
        $this->assertStringContainsString('<svg', $result);
        $this->assertStringContainsString('viewBox="0 0 24 24"', $result);
    }

    /** @test */
    public function it_formats_timestamp_correctly()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        // Should contain formatted date
        $this->assertStringContainsString('January 15, 2024', $result);
    }

    /** @test */
    public function it_includes_relative_time()
    {
        $deletedAt = Carbon::now()->subDays(2)->toDateTimeString();
        $result = $this->indicator->render($deletedAt);

        // Should contain relative time like "2 days ago"
        $this->assertStringContainsString('ago', $result);
    }

    /** @test */
    public function it_uses_admin_context_styling_by_default()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt, 'admin');

        $this->assertStringContainsString('text-base', $result);
    }

    /** @test */
    public function it_uses_public_context_styling()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt, 'public');

        $this->assertStringContainsString('text-sm', $result);
    }

    /** @test */
    public function it_includes_error_color_classes()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        $this->assertStringContainsString('text-error', $result);
        $this->assertStringContainsString('border-error', $result);
        $this->assertStringContainsString('bg-error', $result);
    }

    /** @test */
    public function it_includes_dark_mode_classes()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        $this->assertStringContainsString('dark:', $result);
    }

    /** @test */
    public function it_handles_invalid_timestamp_gracefully()
    {
        $deletedAt = 'invalid-date';
        $result = $this->indicator->render($deletedAt);

        // Should still render something
        $this->assertStringContainsString('soft-delete-indicator', $result);
        $this->assertStringContainsString('This record has been deleted', $result);
    }

    /** @test */
    public function it_uses_rounded_border_styling()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        $this->assertStringContainsString('rounded-lg', $result);
        $this->assertStringContainsString('border', $result);
    }

    /** @test */
    public function it_includes_padding_and_margin()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        $this->assertStringContainsString('mb-4', $result);
        $this->assertStringContainsString('p-4', $result);
    }

    /** @test */
    public function it_displays_badge_before_timestamp()
    {
        $deletedAt = '2024-01-15 10:30:00';
        $result = $this->indicator->render($deletedAt);

        $badgePos = strpos($result, 'This record has been deleted');
        $timestampPos = strpos($result, 'Deleted:');

        $this->assertLessThan($timestampPos, $badgePos);
    }
}
