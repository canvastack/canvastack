<?php

declare(strict_types=1);

namespace Canvastack\Canvastack\Components\Table;

use Illuminate\Support\Facades\Log;

/**
 * Warning System Component
 * 
 * Provides configurable warning system for connection override detection.
 * Supports multiple warning methods: log, toast, or both.
 * 
 * @package Canvastack\Canvastack\Components\Table
 */
class WarningSystem
{
    /**
     * Array of generated toast scripts.
     * 
     * @var array<string>
     */
    protected array $toastScripts = [];

    /**
     * Check if warnings are enabled.
     * 
     * Reads configuration from config/canvastack.php to determine
     * if connection override warnings should be triggered.
     * 
     * @return bool True if warnings are enabled, false otherwise
     */
    public function isEnabled(): bool
    {
        return (bool) config('canvastack.table.connection_warning.enabled', true);
    }

    /**
     * Get warning method from config.
     * 
     * Returns the configured warning method:
     * - 'log': Write to Laravel log only
     * - 'toast': Show browser toast notification only
     * - 'both': Both log and toast
     * 
     * @return string Warning method ('log', 'toast', or 'both')
     */
    public function getMethod(): string
    {
        return (string) config('canvastack.table.connection_warning.method', 'log');
    }

    /**
     * Trigger connection override warning.
     * 
     * Checks if warnings are enabled and executes the appropriate
     * warning method based on configuration.
     * 
     * @param string $modelClass Model class name
     * @param string $modelConnection Model's default connection
     * @param string $overrideConnection Manually specified override connection
     * @return void
     */
    public function warnConnectionOverride(
        string $modelClass,
        string $modelConnection,
        string $overrideConnection
    ): void {
        // Check if warnings are enabled
        if (!$this->isEnabled()) {
            return;
        }

        // Format warning message
        $message = $this->formatMessage(
            $modelClass,
            $modelConnection,
            $overrideConnection
        );

        // Execute warning based on configured method
        $method = $this->getMethod();

        if ($method === 'log' || $method === 'both') {
            $this->logWarning($message);
        }

        if ($method === 'toast' || $method === 'both') {
            $script = $this->generateToastScript($message);
            $this->toastScripts[] = $script;
        }
    }

    /**
     * Get all generated toast scripts.
     * 
     * Returns all toast notification scripts that have been generated
     * during the request. These should be included in the rendered output.
     * 
     * @return array<string> Array of JavaScript code strings
     */
    public function getToastScripts(): array
    {
        return $this->toastScripts;
    }

    /**
     * Get all toast scripts as a single string.
     * 
     * Convenience method that joins all toast scripts into a single
     * string for easy inclusion in rendered output.
     * 
     * @return string Combined JavaScript code
     */
    public function renderToastScripts(): string
    {
        return implode("\n", $this->toastScripts);
    }

    /**
     * Write warning to Laravel log.
     * 
     * Logs the warning message to Laravel's log file with WARNING level.
     * Includes full context about the connection mismatch.
     * 
     * @param string $message Warning message
     * @return void
     */
    protected function logWarning(string $message): void
    {
        Log::warning($message);
    }

    /**
     * Generate JavaScript toast notification.
     * 
     * Generates JavaScript code for displaying an Alpine.js toast notification
     * in the browser. The toast will appear when the page loads.
     * 
     * The generated script:
     * - Uses DOMContentLoaded event to ensure DOM is ready
     * - Creates a toast notification with warning styling
     * - Includes the full warning message with context
     * - Auto-dismisses after 10 seconds
     * - Can be manually dismissed by user
     * 
     * @param string $message Warning message
     * @return string JavaScript code for toast notification
     */
    protected function generateToastScript(string $message): string
    {
        // Escape message for JavaScript (prevent XSS)
        $escapedMessage = addslashes($message);
        $escapedMessage = str_replace(["\r", "\n"], ['', '\n'], $escapedMessage);
        
        // Generate JavaScript code for Alpine.js toast notification
        $script = <<<JAVASCRIPT
<script>
window.addEventListener('DOMContentLoaded', function() {
    // Create toast container if it doesn't exist
    if (!document.getElementById('canvastack-toast-container')) {
        const container = document.createElement('div');
        container.id = 'canvastack-toast-container';
        container.className = 'fixed top-4 right-4 z-50 space-y-2';
        document.body.appendChild(container);
    }
    
    // Create toast element
    const toast = document.createElement('div');
    toast.className = 'alert alert-warning shadow-lg max-w-md';
    toast.setAttribute('x-data', '{ show: true }');
    toast.setAttribute('x-show', 'show');
    toast.setAttribute('x-transition', '');
    toast.innerHTML = `
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current flex-shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <h3 class="font-bold">Connection Override Warning</h3>
                <div class="text-xs whitespace-pre-line">{$escapedMessage}</div>
            </div>
        </div>
        <button class="btn btn-sm btn-ghost" onclick="this.closest('[x-data]').__x.\$data.show = false">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    `;
    
    // Add toast to container
    document.getElementById('canvastack-toast-container').appendChild(toast);
    
    // Auto-dismiss after 10 seconds
    setTimeout(function() {
        if (toast.__x) {
            toast.__x.\$data.show = false;
            // Remove from DOM after transition
            setTimeout(function() {
                toast.remove();
            }, 300);
        }
    }, 10000);
});
</script>
JAVASCRIPT;
        
        return $script;
    }

    /**
     * Format warning message.
     * 
     * Creates a formatted warning message with full context about
     * the connection override situation.
     * 
     * @param string $modelClass Model class name
     * @param string $modelConnection Model's default connection
     * @param string $overrideConnection Override connection
     * @return string Formatted warning message
     */
    protected function formatMessage(
        string $modelClass,
        string $modelConnection,
        string $overrideConnection
    ): string {
        return sprintf(
            "Connection override detected:\n" .
            "Model: %s\n" .
            "Model Connection: %s\n" .
            "Override Connection: %s\n" .
            "This may cause unexpected behavior if the model has connection-specific logic.",
            $modelClass,
            $modelConnection,
            $overrideConnection
        );
    }
}
