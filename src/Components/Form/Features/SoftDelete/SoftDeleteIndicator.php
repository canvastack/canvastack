<?php

namespace Canvastack\Canvastack\Components\Form\Features\SoftDelete;

use Carbon\Carbon;

/**
 * SoftDeleteIndicator - Renders visual indicators for soft-deleted records.
 *
 * Provides badge and timestamp display for soft-deleted records in forms.
 *
 * Requirements: 8.5, 8.6
 */
class SoftDeleteIndicator
{
    /**
     * Render the soft delete indicator badge and timestamp.
     *
     * @param string|null $deletedAt The deletion timestamp
     * @param string $context The rendering context (admin or public)
     * @param array $options Additional options (showActions, modelClass, modelId, showPermanentDelete)
     * @return string HTML for the indicator
     */
    public function render(?string $deletedAt, string $context = 'admin', array $options = []): string
    {
        if (!$deletedAt) {
            return '';
        }

        $badge = $this->renderBadge($context);
        $timestamp = $this->renderTimestamp($deletedAt, $context);

        return <<<HTML
        <div class="soft-delete-indicator mb-4 p-4 rounded-lg border border-error/20 bg-error/5 dark:bg-error/10">
            {$badge}
            {$timestamp}
        </div>
        HTML;
    }

    /**
     * Render the deletion indicator badge.
     *
     * @param string $context The rendering context
     * @return string HTML for the badge
     */
    protected function renderBadge(string $context): string
    {
        $classes = $this->getBadgeClasses($context);

        return <<<HTML
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="{$classes}">
                This record has been deleted
            </span>
        </div>
        HTML;
    }

    /**
     * Render the deletion timestamp.
     *
     * @param string $deletedAt The deletion timestamp
     * @param string $context The rendering context
     * @return string HTML for the timestamp
     */
    protected function renderTimestamp(string $deletedAt, string $context): string
    {
        $formattedDate = $this->formatTimestamp($deletedAt);
        $relativeDate = $this->getRelativeTime($deletedAt);
        $classes = $this->getTimestampClasses($context);

        return <<<HTML
        <div class="{$classes}">
            <span class="font-medium">Deleted:</span>
            <span>{$formattedDate}</span>
            <span class="text-sm opacity-75">({$relativeDate})</span>
        </div>
        HTML;
    }

    /**
     * Get badge CSS classes based on context.
     *
     * @param string $context The rendering context
     * @return string CSS classes
     */
    protected function getBadgeClasses(string $context): string
    {
        $baseClasses = 'font-semibold text-error';

        if ($context === 'admin') {
            return $baseClasses . ' text-base';
        }

        return $baseClasses . ' text-sm';
    }

    /**
     * Get timestamp CSS classes based on context.
     *
     * @param string $context The rendering context
     * @return string CSS classes
     */
    protected function getTimestampClasses(string $context): string
    {
        $baseClasses = 'text-error/80 dark:text-error/90';

        if ($context === 'admin') {
            return $baseClasses . ' text-sm';
        }

        return $baseClasses . ' text-xs';
    }

    /**
     * Format timestamp for display.
     *
     * @param string $deletedAt The deletion timestamp
     * @return string Formatted timestamp
     */
    protected function formatTimestamp(string $deletedAt): string
    {
        try {
            return Carbon::parse($deletedAt)->format('F j, Y \a\t g:i A');
        } catch (\Exception $e) {
            return $deletedAt;
        }
    }

    /**
     * Get relative time (e.g., "2 days ago").
     *
     * @param string $deletedAt The deletion timestamp
     * @return string Relative time string
     */
    protected function getRelativeTime(string $deletedAt): string
    {
        try {
            return Carbon::parse($deletedAt)->diffForHumans();
        } catch (\Exception $e) {
            return '';
        }
    }
}
