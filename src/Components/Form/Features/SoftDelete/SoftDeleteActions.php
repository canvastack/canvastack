<?php

namespace Canvastack\Canvastack\Components\Form\Features\SoftDelete;

/**
 * SoftDeleteActions - Renders restore and permanent delete buttons.
 *
 * Provides action buttons for soft-deleted records in forms.
 *
 * Requirements: 8.7, 8.8, 8.9
 */
class SoftDeleteActions
{
    /**
     * Render restore and permanent delete action buttons.
     *
     * @param string $modelClass The model class name
     * @param mixed $modelId The model ID
     * @param string $context The rendering context (admin or public)
     * @param bool $showPermanentDelete Whether to show permanent delete option
     * @return string HTML for the action buttons
     */
    public function render(
        string $modelClass,
        $modelId,
        string $context = 'admin',
        bool $showPermanentDelete = false
    ): string {
        $restoreButton = $this->renderRestoreButton($modelClass, $modelId, $context);
        $permanentDeleteButton = $showPermanentDelete
            ? $this->renderPermanentDeleteButton($modelClass, $modelId, $context)
            : '';

        return <<<HTML
        <div class="soft-delete-actions flex gap-3 mb-6">
            {$restoreButton}
            {$permanentDeleteButton}
        </div>
        HTML;
    }

    /**
     * Render the restore button.
     *
     * @param string $modelClass The model class name
     * @param mixed $modelId The model ID
     * @param string $context The rendering context
     * @return string HTML for the restore button
     */
    protected function renderRestoreButton(string $modelClass, $modelId, string $context): string
    {
        $classes = $this->getRestoreButtonClasses($context);
        $encodedClass = base64_encode($modelClass);

        return <<<HTML
        <button 
            type="button"
            class="{$classes}"
            onclick="restoreSoftDeletedRecord('{$encodedClass}', '{$modelId}')"
            data-model-class="{$encodedClass}"
            data-model-id="{$modelId}"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <span>Restore Record</span>
        </button>
        HTML;
    }

    /**
     * Render the permanent delete button.
     *
     * @param string $modelClass The model class name
     * @param mixed $modelId The model ID
     * @param string $context The rendering context
     * @return string HTML for the permanent delete button
     */
    protected function renderPermanentDeleteButton(string $modelClass, $modelId, string $context): string
    {
        $classes = $this->getPermanentDeleteButtonClasses($context);
        $encodedClass = base64_encode($modelClass);

        return <<<HTML
        <button 
            type="button"
            class="{$classes}"
            onclick="permanentlyDeleteRecord('{$encodedClass}', '{$modelId}')"
            data-model-class="{$encodedClass}"
            data-model-id="{$modelId}"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Delete Permanently</span>
        </button>
        HTML;
    }

    /**
     * Get restore button CSS classes based on context.
     *
     * @param string $context The rendering context
     * @return string CSS classes
     */
    protected function getRestoreButtonClasses(string $context): string
    {
        $baseClasses = 'btn btn-success gap-2 transition-all duration-200 hover:scale-105';

        if ($context === 'admin') {
            return $baseClasses . ' btn-md';
        }

        return $baseClasses . ' btn-sm';
    }

    /**
     * Get permanent delete button CSS classes based on context.
     *
     * @param string $context The rendering context
     * @return string CSS classes
     */
    protected function getPermanentDeleteButtonClasses(string $context): string
    {
        $baseClasses = 'btn btn-error gap-2 transition-all duration-200 hover:scale-105';

        if ($context === 'admin') {
            return $baseClasses . ' btn-md';
        }

        return $baseClasses . ' btn-sm';
    }

    /**
     * Render JavaScript for handling restore and delete actions.
     *
     * @param string|null $csrfToken Optional CSRF token (for testing)
     * @param string|null $restoreUrl Optional restore URL (for testing)
     * @param string|null $deleteUrl Optional delete URL (for testing)
     * @return string JavaScript code
     */
    public function renderScript(?string $csrfToken = null, ?string $restoreUrl = null, ?string $deleteUrl = null): string
    {
        $csrfToken = $csrfToken ?? $this->getCsrfToken();
        $restoreUrl = $restoreUrl ?? $this->getRestoreUrl();
        $deleteUrl = $deleteUrl ?? $this->getDeleteUrl();

        return <<<JS
        <script>
        function restoreSoftDeletedRecord(encodedClass, modelId) {
            if (!confirm('Are you sure you want to restore this record?')) {
                return;
            }

            fetch('{$restoreUrl}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{$csrfToken}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    model_class: encodedClass,
                    model_id: modelId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Record restored successfully!');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to restore record'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while restoring the record');
            });
        }

        function permanentlyDeleteRecord(encodedClass, modelId) {
            if (!confirm('WARNING: This will permanently delete the record. This action cannot be undone. Are you sure?')) {
                return;
            }

            // Double confirmation for permanent delete
            if (!confirm('This is your final warning. The record will be permanently deleted. Continue?')) {
                return;
            }

            fetch('{$deleteUrl}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{$csrfToken}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    model_class: encodedClass,
                    model_id: modelId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Record permanently deleted');
                    window.location.href = data.redirect || '/';
                } else {
                    alert('Error: ' + (data.message || 'Failed to delete record'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting the record');
            });
        }
        </script>
        JS;
    }

    /**
     * Get CSRF token.
     *
     * @return string
     */
    protected function getCsrfToken(): string
    {
        if (function_exists('csrf_token')) {
            return csrf_token();
        }

        return '';
    }

    /**
     * Get restore URL.
     *
     * @return string
     */
    protected function getRestoreUrl(): string
    {
        if (function_exists('route')) {
            return route('canvastack.soft-delete.restore');
        }

        return '/canvastack/soft-delete/restore';
    }

    /**
     * Get delete URL.
     *
     * @return string
     */
    protected function getDeleteUrl(): string
    {
        if (function_exists('route')) {
            return route('canvastack.soft-delete.force-delete');
        }

        return '/canvastack/soft-delete/force-delete';
    }
}
