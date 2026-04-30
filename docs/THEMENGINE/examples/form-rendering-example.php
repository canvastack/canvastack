<?php
/**
 * Form Rendering Example
 * 
 * Demonstrates form element rendering with all three templates:
 * - Bootstrap 4 (default)
 * - Bootstrap 5 (canvasign)
 * - TailwindCSS (canvas)
 * 
 * @version 2.0.0
 * @author CanvaStack Team
 */

// ============================================================================
// SETUP
// ============================================================================

// Ensure CanvaStack is loaded
require_once __DIR__ . '/../../../../vendor/autoload.php';

// Get current template (or set for testing)
$currentTemplate = canvastack_current_template() ?: 'default';

echo "<!DOCTYPE html>\n";
echo "<html>\n";
echo "<head>\n";
echo "    <title>Form Rendering Example - {$currentTemplate}</title>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";

// Load framework-specific assets
if ($currentTemplate === 'default') {
    echo "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
    echo "    <link href='https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.min.css' rel='stylesheet'>\n";
} elseif ($currentTemplate === 'canvasign') {
    echo "    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>\n";
    echo "    <link href='https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/styles/choices.min.css' rel='stylesheet'>\n";
} elseif ($currentTemplate === 'canvas') {
    echo "    <script src='https://cdn.tailwindcss.com'></script>\n";
}

echo "</head>\n";
echo "<body>\n";
echo "    <div class='container mx-auto p-4'>\n";
echo "        <h1 class='text-3xl font-bold mb-4'>Form Rendering Example</h1>\n";
echo "        <p class='mb-4'>Current Template: <strong>{$currentTemplate}</strong></p>\n";

// ============================================================================
// EXAMPLE 1: TAB HEADERS
// ============================================================================

echo "        <h2 class='text-2xl font-bold mt-6 mb-3'>1. Tab Headers</h2>\n";
echo "        <ul class='nav nav-tabs'>\n";

// Render tab headers using helper function
// The helper automatically delegates to the correct adapter based on active template
echo canvastack_form_create_header_tab('Users', 'users-tab', true, false);
echo canvastack_form_create_header_tab('Settings', 'settings-tab', false, false);
echo canvastack_form_create_header_tab('Profile', 'profile-tab', false, false);

echo "        </ul>\n";

// Show HTML output for educational purposes
echo "        <div class='mt-3 p-3 bg-gray-100 rounded'>\n";
echo "            <strong>HTML Output:</strong>\n";
echo "            <pre class='text-sm'>" . htmlspecialchars(canvastack_form_create_header_tab('Users', 'users-tab', true, false)) . "</pre>\n";
echo "        </div>\n";

// ============================================================================
// EXAMPLE 2: ALERT MESSAGES
// ============================================================================

echo "        <h2 class='text-2xl font-bold mt-6 mb-3'>2. Alert Messages</h2>\n";

// Success alert
echo canvastack_form_alert_message('Operation completed successfully!', 'success', 'Success', 'msg-success', false);

// Warning alert
echo canvastack_form_alert_message('Please review your input.', 'warning', 'Warning', 'msg-warning', false);

// Danger alert
echo canvastack_form_alert_message('An error occurred.', 'danger', 'Error', 'msg-danger', false);

// Info alert
echo canvastack_form_alert_message('This is an informational message.', 'info', 'Info', 'msg-info', false);

// Show HTML output
echo "        <div class='mt-3 p-3 bg-gray-100 rounded'>\n";
echo "            <strong>HTML Output (Success Alert):</strong>\n";
echo "            <pre class='text-sm'>" . htmlspecialchars(canvastack_form_alert_message('Success!', 'success', 'Done', 'msg', false)) . "</pre>\n";
echo "        </div>\n";

// ============================================================================
// EXAMPLE 3: CHECKBOXES
// ============================================================================

echo "        <h2 class='text-2xl font-bold mt-6 mb-3'>3. Checkboxes</h2>\n";

// Render checkboxes with different colors
echo canvastack_form_checkList('terms', '1', 'I agree to the terms and conditions', false, 'primary', 'terms-cb', null);
echo canvastack_form_checkList('newsletter', '1', 'Subscribe to newsletter', true, 'success', 'newsletter-cb', null);
echo canvastack_form_checkList('notifications', '1', 'Enable notifications', false, 'warning', 'notifications-cb', null);

// Show HTML output
echo "        <div class='mt-3 p-3 bg-gray-100 rounded'>\n";
echo "            <strong>HTML Output (Checkbox):</strong>\n";
echo "            <pre class='text-sm'>" . htmlspecialchars(canvastack_form_checkList('terms', '1', 'I agree', false, 'primary', 'terms-cb', null)) . "</pre>\n";
echo "        </div>\n";

// ============================================================================
// EXAMPLE 4: SELECT ELEMENTS
// ============================================================================

echo "        <h2 class='text-2xl font-bold mt-6 mb-3'>4. Select Elements</h2>\n";

// Country select
$countries = [
    'US' => 'United States',
    'UK' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    'DE' => 'Germany',
    'FR' => 'France',
    'JP' => 'Japan',
];

echo "        <div class='mb-3'>\n";
echo "            <label>Select Country:</label>\n";
echo canvastack_form_selectbox('country', $countries, 'US', ['class' => 'form-control'], true, false);
echo "        </div>\n";

// Role select
$roles = [
    'admin' => 'Administrator',
    'editor' => 'Editor',
    'author' => 'Author',
    'subscriber' => 'Subscriber',
];

echo "        <div class='mb-3'>\n";
echo "            <label>Select Role:</label>\n";
echo canvastack_form_selectbox('role', $roles, 'editor', ['class' => 'form-control'], true, false);
echo "        </div>\n";

// Show HTML output
echo "        <div class='mt-3 p-3 bg-gray-100 rounded'>\n";
echo "            <strong>HTML Output (Select):</strong>\n";
echo "            <pre class='text-sm'>" . htmlspecialchars(canvastack_form_selectbox('country', $countries, 'US', ['class' => 'form-control'], true, false)) . "</pre>\n";
echo "        </div>\n";

// ============================================================================
// EXAMPLE 5: FRAMEWORK COMPARISON
// ============================================================================

echo "        <h2 class='text-2xl font-bold mt-6 mb-3'>5. Framework Comparison</h2>\n";
echo "        <table class='table table-bordered'>\n";
echo "            <thead>\n";
echo "                <tr>\n";
echo "                    <th>Feature</th>\n";
echo "                    <th>Bootstrap 4</th>\n";
echo "                    <th>Bootstrap 5</th>\n";
echo "                    <th>TailwindCSS</th>\n";
echo "                </tr>\n";
echo "            </thead>\n";
echo "            <tbody>\n";
echo "                <tr>\n";
echo "                    <td>Toggle Attribute</td>\n";
echo "                    <td><code>data-toggle</code></td>\n";
echo "                    <td><code>data-bs-toggle</code></td>\n";
echo "                    <td><code>data-toggle</code> (custom)</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                    <td>Dismiss Attribute</td>\n";
echo "                    <td><code>data-dismiss</code></td>\n";
echo "                    <td><code>data-bs-dismiss</code></td>\n";
echo "                    <td><code>data-dismiss</code> (custom)</td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                    <td>Hide Class</td>\n";
echo "                    <td><code>hide</code></td>\n";
echo "                    <td><code>d-none</code></td>\n";
echo "                    <td><code>hidden</code></td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                    <td>Float Right</td>\n";
echo "                    <td><code>pull-right</code></td>\n";
echo "                    <td><code>float-end</code></td>\n";
echo "                    <td><code>ml-auto</code></td>\n";
echo "                </tr>\n";
echo "                <tr>\n";
echo "                    <td>Select Plugin</td>\n";
echo "                    <td>Chosen.js</td>\n";
echo "                    <td>Choices.js</td>\n";
echo "                    <td>Native</td>\n";
echo "                </tr>\n";
echo "            </tbody>\n";
echo "        </table>\n";

// ============================================================================
// EXAMPLE 6: SWITCHING TEMPLATES
// ============================================================================

echo "        <h2 class='text-2xl font-bold mt-6 mb-3'>6. Switching Templates</h2>\n";
echo "        <p>To switch templates, update your configuration:</p>\n";
echo "        <pre class='bg-gray-100 p-3 rounded'>\n";
echo "// config/canvastack.templates.php\n";
echo "return [\n";
echo "    'template' => 'canvasign', // 'default', 'canvasign', or 'canvas'\n";
echo "];\n";
echo "        </pre>\n";

echo "        <p class='mt-3'>Then clear caches:</p>\n";
echo "        <pre class='bg-gray-100 p-3 rounded'>\n";
echo "php artisan config:clear\n";
echo "php artisan view:clear\n";
echo "php artisan cache:clear\n";
echo "        </pre>\n";

// ============================================================================
// FOOTER
// ============================================================================

echo "        <hr class='my-6'>\n";
echo "        <p class='text-center text-gray-600'>\n";
echo "            <strong>CanvaStack Theme Engine v2.0.0</strong><br>\n";
echo "            Built with ❤️ by CanvaStack Team<br>\n";
echo "            Alhamdulillah\n";
echo "        </p>\n";
echo "    </div>\n";

// Load framework-specific JavaScript
if ($currentTemplate === 'default') {
    echo "    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>\n";
    echo "    <script src='https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js'></script>\n";
    echo "    <script src='https://cdn.jsdelivr.net/npm/chosen-js@1.8.7/chosen.jquery.min.js'></script>\n";
    echo "    <script>\n";
    echo "        $(document).ready(function() {\n";
    echo "            $('.chosen-select-deselect').chosen({ allow_single_deselect: true, width: '100%' });\n";
    echo "        });\n";
    echo "    </script>\n";
} elseif ($currentTemplate === 'canvasign') {
    echo "    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>\n";
    echo "    <script src='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'></script>\n";
    echo "    <script src='https://cdn.jsdelivr.net/npm/choices.js@10.2.0/public/assets/scripts/choices.min.js'></script>\n";
    echo "    <script>\n";
    echo "        document.addEventListener('DOMContentLoaded', function() {\n";
    echo "            const selects = document.querySelectorAll('.form-select');\n";
    echo "            selects.forEach(function(select) {\n";
    echo "                new Choices(select, { removeItemButton: true });\n";
    echo "            });\n";
    echo "        });\n";
    echo "    </script>\n";
}

echo "</body>\n";
echo "</html>\n";

// ============================================================================
// NOTES
// ============================================================================

/*
 * Key Concepts Demonstrated:
 * 
 * 1. Helper Functions: All form elements are rendered using helper functions
 *    that automatically delegate to the correct adapter.
 * 
 * 2. Template Detection: The system automatically detects the active template
 *    and uses the appropriate adapter (DefaultAdapter, Bootstrap5Adapter, or
 *    TailwindAdapter).
 * 
 * 3. Zero Code Changes: The same helper function calls work with all three
 *    templates without any code modifications.
 * 
 * 4. Framework-Specific Output: Each adapter generates HTML with the correct
 *    CSS classes and data attributes for its framework.
 * 
 * 5. Asset Loading: Framework-specific CSS and JavaScript are loaded based
 *    on the active template.
 * 
 * Usage:
 * 
 * 1. Copy this file to your project
 * 2. Create a route to access it
 * 3. Switch templates in config/canvastack.templates.php
 * 4. Clear caches and reload the page
 * 5. Observe how the same code produces different output for each framework
 */
