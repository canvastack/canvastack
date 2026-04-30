<?php

namespace Canvastack\Canvastack\Library\Theme\Adapters;

use Collective\Html\FormFacade;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;

/**
 * TailwindAdapter — TailwindCSS Theme Adapter
 *
 * Generates HTML using TailwindCSS utility classes for the `canvas` template.
 * This adapter produces markup that is entirely free of Bootstrap-specific
 * classes and attributes. Custom JavaScript (canvas-scripts.js) handles
 * interactive behaviours such as tab switching, modal toggling, and dismissal
 * using standard `data-toggle` / `data-dismiss` attributes.
 *
 * Key differences from DefaultAdapter (Bootstrap 4):
 *
 * - Tab header uses `flex border-b cursor-pointer` utility classes
 * - Alert uses `flex items-start gap-3 p-4 rounded-lg` utility classes
 * - Checkbox uses `flex items-center gap-2` utility classes
 * - Select uses `form-input` class (no Chosen.js)
 * - Modal uses `fixed inset-0 z-50 flex items-center justify-center`
 * - Hide class is `hidden` instead of `hide` / `d-none`
 * - Float-right class is `ml-auto` instead of `pull-right` / `float-end`
 * - Action buttons use Tailwind utility classes (no `btn-xs` / `btn-sm`)
 * - Table class uses `w-full text-sm text-left` utility classes
 * - No Bootstrap-specific classes: `alert-block`, `nav-item`, `ckbox`,
 *   `chosen-select`, `btn-xs`, `pull-right`, `hide`, `d-none`
 *
 * @package    Canvastack\Canvastack\Library\Theme\Adapters
 * @author     wisnuwidi@canvastack.com
 * @copyright  Canvastack
 * @see        ThemeAdapterInterface
 */
class TailwindAdapter implements ThemeAdapterInterface
{
    // ── Utility Methods ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     * Tailwind: 'data-toggle' (handled by custom canvas-scripts.js)
     */
    public function getDataToggleAttribute(): string
    {
        return 'data-toggle';
    }

    /**
     * {@inheritdoc}
     * Tailwind: 'data-dismiss' (handled by custom canvas-scripts.js)
     */
    public function getDismissAttribute(): string
    {
        return 'data-dismiss';
    }

    /**
     * Render page-level action buttons — Tailwind output.
     * Falls back to the Bootstrap 4 helper for now; override when Tailwind
     * page-action styling is needed.
     */
    public function renderPageActionButtons(object $route_info): string
    {
        return canvastack_action_buttons($route_info);
    }

    /**
     * {@inheritdoc}
     * Tailwind: 'hidden'
     */
    public function getHideClass(): string
    {
        return 'hidden';
    }

    /**
     * {@inheritdoc}
     * Tailwind: 'ml-auto'
     */
    public function getFloatRightClass(): string
    {
        return 'ml-auto';
    }

    /**
     * {@inheritdoc}
     * Tailwind: 'mr-auto' (or 'float-left' for compatibility)
     */
    public function getFloatLeftClass(): string
    {
        return 'mr-auto';
    }

    /**
     * {@inheritdoc}
     * Tailwind: 'form-input'
     */
    public function getSelectBoxClass(): string
    {
        return 'form-input';
    }

    /**
     * {@inheritdoc}
     * Tailwind DataTable class string — uses Tailwind utility classes.
     * No Bootstrap-specific `animated`, `fadeIn`, or table modifier classes.
     *
     * Requirements: 9.5
     */
    public function getTableClass(): string
    {
        return 'CanvaStack-table w-full text-sm text-left dataTable repeater display responsive nowrap';
    }

    // ── Form Methods ──────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind tab header item.
     * Uses Tailwind utility classes (`flex`, `border-b`, `cursor-pointer`)
     * instead of Bootstrap nav-item / nav-link classes.
     * Custom JS (canvas-scripts.js) handles tab switching via data-toggle.
     *
     * Requirements: 6.1
     */
    public function renderTabHeader(
        string $data,
        string $pointer,
        string|false $active,
        string|false $class
    ): string {
        $dataEscaped    = $this->escapeHtml($data);
        $pointerEscaped = $this->escapeHtml($pointer);
        $activeEscaped  = $this->escapeHtml($active);
        $classEscaped   = $this->escapeHtml($class);

        $tabName      = ucwords(str_replace('_', ' ', $dataEscaped));
        $ariaSelected = 'false';
        $activeClass  = '';
        $classTag     = '';

        if ($active) {
            // Active tab: highlighted border and text colour
            $activeClass  = ' border-b-2 border-blue-600 text-blue-600';
            $ariaSelected = 'true';
        }

        if ($class) {
            $classTag = '<i class="' . $classEscaped . '" aria-hidden="true"></i>';
        }

        // Tailwind: flex item with cursor-pointer; data-toggle handled by custom JS
        $string = '<li class="flex cursor-pointer" role="presentation">'
            . '<a id="' . $pointerEscaped . '-tab"'
            . ' class="flex items-center gap-2 px-4 py-2 text-sm font-medium border-b-2 border-transparent hover:border-blue-400 hover:text-blue-500 transition-colors' . $activeClass . '"'
            . ' data-toggle="tab"'
            . ' role="tab"'
            . ' href="#' . $pointerEscaped . '"'
            . ' aria-selected="' . $ariaSelected . '"'
            . ' aria-controls="' . $pointerEscaped . '">'
            . $classTag . $tabName
            . '</a></li>';

        return SafeHtml::mark($string);
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind tab content pane.
     * Uses Tailwind utility classes for visibility toggling.
     * The `hidden` class is toggled by custom JS (canvas-scripts.js).
     *
     * Requirements: 6.1
     */
    public function renderTabContent(
        string $data,
        string $pointer,
        bool $active
    ): string {
        $pointerEscaped = $this->escapeHtml($pointer);

        $hiddenClass = $active ? '' : ' hidden';
        $ariaHidden  = $active ? 'false' : 'true';

        $string = '<div id="' . $pointerEscaped . '"'
            . ' class="tab-pane' . $hiddenClass . '"'
            . ' role="tabpanel"'
            . ' aria-hidden="' . $ariaHidden . '"'
            . ' aria-labelledby="' . $pointerEscaped . '-tab">'
            . $data
            . '</div>';

        return SafeHtml::mark($string);
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind dismissable alert message.
     * Uses Tailwind utility classes: `flex items-start gap-3 p-4 rounded-lg`.
     * No Bootstrap-specific classes (`alert-block`, `alert-{type}` modifier).
     * Dismiss is handled by custom JS via `data-dismiss="alert"`.
     *
     * Alert type → Tailwind colour mapping:
     *   success → green-50 / green-800 / green-200
     *   danger  → red-50 / red-800 / red-200
     *   warning → yellow-50 / yellow-800 / yellow-200
     *   info    → blue-50 / blue-800 / blue-200
     *
     * Requirements: 6.2
     */
    public function renderAlertMessage(
        string|array $message,
        string $type,
        string $title,
        string $prefix,
        string|false $extra
    ): string {
        $type   = $this->escapeHtml($type);
        $prefix = $this->escapeHtml($prefix);
        $title  = $this->escapeHtml($title);

        // Map alert type to Tailwind colour classes
        $colourMap = [
            'success' => 'bg-green-50 text-green-800 border border-green-200',
            'danger'  => 'bg-red-50 text-red-800 border border-red-200',
            'warning' => 'bg-yellow-50 text-yellow-800 border border-yellow-200',
            'info'    => 'bg-blue-50 text-blue-800 border border-blue-200',
        ];
        $colourClass = $colourMap[$type] ?? 'bg-gray-50 text-gray-800 border border-gray-200';

        $content_message = null;
        if (is_array($message) && 'success' !== $type) {
            $content_message = '<ul class="mt-2 space-y-1 text-sm">';
            foreach ($message as $mfield => $mData) {
                $mfieldEscaped = $this->escapeHtml($mfield);
                $mfieldLabel   = ucwords(str_replace('_', ' ', $mfieldEscaped));

                $content_message .= '<li class="font-medium"><div>';
                $content_message .= '<label for="' . $mfieldEscaped . '" class="block text-xs font-semibold uppercase tracking-wide">' . $mfieldLabel . '</label>';
                if (is_array($mData)) {
                    $content_message .= '<ul class="mt-1 space-y-0.5">';
                    foreach ($mData as $imData) {
                        $imDataEscaped    = $this->escapeHtml($imData);
                        $content_message .= '<li>';
                        $content_message .= '<label for="' . $mfieldEscaped . '" class="text-sm">' . $imDataEscaped . '</label>';
                        $content_message .= '</li>';
                    }
                    $content_message .= '</ul>';
                } else {
                    $mDataEscaped     = $this->escapeHtml($mData);
                    $content_message .= '<ul class="mt-1 space-y-0.5"><li><label for="' . $mfieldEscaped . '" class="text-sm">' . $mDataEscaped . '</label></li></ul>';
                }
                $content_message .= '</div></li>';
            }
            $content_message .= '</ul>';
        } else {
            if (!is_array($message)) {
                $content_message = $this->escapeHtml($message);
            }
        }

        $prefix_tag = '';
        if (false !== $prefix && '' !== $prefix) {
            $prefix_tag = '<strong><i class="fa ' . $prefix . '" aria-hidden="true"></i> &nbsp;' . $title . '</strong>';
        }

        $extraHtml = '';
        if (false !== $extra && '' !== $extra) {
            $extraHtml = strip_tags($extra, '<br><b><i><strong><em><span><div><p><ul><li><a>');
        }

        $ariaLive = ($type === 'danger' || $type === 'warning') ? 'assertive' : 'polite';

        // Tailwind: flex items-start gap-3 p-4 rounded-lg — no alert-block, no Bootstrap classes
        $o  = '<div class="flex items-start gap-3 p-4 rounded-lg ' . $colourClass . '" role="alert" aria-live="' . $ariaLive . '" aria-atomic="true">';
        $o .= '<div class="flex-1">';
        if (!is_array($message)) {
            $o .= '<p class="text-sm">' . $prefix_tag . ' ' . $content_message . '</p>';
        } else {
            $o .= '<p class="text-sm font-medium">' . $prefix_tag . '</p>' . $content_message;
        }
        $o .= $extraHtml;
        $o .= '</div>';
        // Dismiss button — handled by custom JS via data-dismiss="alert"
        $o .= '<button type="button" class="ml-auto -mx-1.5 -my-1.5 rounded-lg p-1.5 inline-flex items-center justify-center h-8 w-8 hover:bg-black/10 focus:ring-2 focus:ring-offset-1 transition-colors" data-dismiss="alert" aria-label="Close alert">';
        $o .= '<span aria-hidden="true">&times;</span>';
        $o .= '</button>';
        $o .= '</div>';

        return $o;
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind checkbox element.
     * Uses `flex items-center gap-2` utility classes.
     * No Bootstrap-specific classes (`ckbox`, `form-check`, etc.).
     *
     * Requirements: 6.4
     *
     * @throws \InvalidArgumentException If inputNode contains invalid characters or dangerous attributes.
     */
    public function renderCheckList(
        mixed $name,
        string|false $value,
        string|false $label,
        bool $checked,
        string $class,
        string|false $id,
        ?string $inputNode
    ): string {
        $nameEscaped  = $this->escapeHtml($name);
        $valueEscaped = $this->escapeHtml($value);
        $labelEscaped = $this->escapeHtml($label);
        $classEscaped = $this->escapeHtml($class);
        $idEscaped    = $this->escapeHtml($id);

        $nameAttr  = '';
        $valueAttr = '';
        $idAttr    = '';
        $idForAttr = '';
        $labelName = '&nbsp;';
        $checkBox  = '';

        if (false !== $name)  $nameAttr  = ' name="' . $nameEscaped . '"';
        if (false !== $value) $valueAttr = ' value="' . $valueEscaped . '"';

        if (false !== $id) {
            $idAttr    = ' id="' . $idEscaped . '"';
            $idForAttr = ' for="' . $idEscaped . '"';
        } else {
            $idAttr    = ' id="' . $nameEscaped . '"';
            $idForAttr = ' for="' . $nameEscaped . '"';
        }

        if (false !== $label) $labelName = '&nbsp; ' . $labelEscaped;

        if (false !== $checked) {
            $checkBox = ' checked="checked" aria-checked="true"';
        } else {
            $checkBox = ' aria-checked="false"';
        }

        // Security: Validate and sanitize inputNode (mirrors DefaultAdapter)
        $inputNodeAttr = '';
        if (!empty($inputNode)) {
            if (!preg_match('/^[a-zA-Z0-9_\-="\'\s.]+$/', $inputNode)) {
                error_log('SECURITY WARNING: Invalid characters in inputNode: ' . $inputNode);
                throw new \InvalidArgumentException('Invalid inputNode format. Only alphanumeric, dashes, quotes, dots, and spaces allowed.');
            }

            $dangerousAttrs = ['onclick', 'onload', 'onerror', 'onmouseover', 'onfocus', 'onblur', 'onchange', 'onsubmit', 'onkeyup', 'onkeydown'];
            foreach ($dangerousAttrs as $attr) {
                if (stripos($inputNode, $attr . '=') !== false) {
                    error_log('SECURITY WARNING: Dangerous attribute blocked in inputNode: ' . $attr);
                    throw new \InvalidArgumentException('Event handler attributes not allowed in inputNode');
                }
            }

            $inputNodeAttr = ' ' . $inputNode;
        }

        // Tailwind: flex items-center gap-2 — no ckbox, no form-check Bootstrap classes
        // The $class modifier is appended as a data attribute for custom JS targeting
        $o = '<div class="flex items-center gap-2 checkbox-' . $classEscaped . '">'
            . '<input class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer" type="checkbox"'
            . $valueAttr . $nameAttr . $idAttr . $checkBox . $inputNodeAttr . '>'
            . '<label class="text-sm font-medium text-gray-700 cursor-pointer"' . $idForAttr . '>' . $labelName . '</label>'
            . '</div>';

        return SafeHtml::mark($o);
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind select element.
     * Uses `form-input` class (no Chosen.js, no Bootstrap `form-select`).
     *
     * Requirements: 6.3
     */
    public function renderSelectBox(
        string $name,
        array $values,
        mixed $selected,
        array $attributes,
        bool $label,
        array|bool $set_first_value
    ): string {
        $twSelectClass = $this->getSelectBoxClass(); // 'form-input'
        $default_attr  = ['class' => $twSelectClass];

        if (!empty($attributes)) {
            $attributes = $this->changeInputAttribute($attributes, 'class', $twSelectClass);
        } else {
            $attributes = $default_attr;
        }

        if (is_array($set_first_value) && !empty($set_first_value)) {
            $values = array_merge_recursive($set_first_value, $values);
        }

        // Laravel FormFacade automatically escapes all values
        $selectbox = FormFacade::select($name, $values, $selected, $attributes);

        return SafeHtml::mark($selectbox);
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind modal wrapper.
     * Uses `fixed inset-0 z-50 flex items-center justify-center` utility classes.
     * No Bootstrap-specific attributes (`data-dismiss`, `data-toggle`).
     * Modal open/close is handled by custom JS (canvas-scripts.js).
     *
     * Requirements: 6.7
     */
    public function renderModalWrapper(
        string $name,
        string $title,
        array $elements
    ): string {
        $nameSafe  = $this->escapeHtml($name);
        $titleSafe = $this->escapeHtml($title);
        $body      = implode('', $elements);

        // Tailwind: fixed inset-0 z-50 flex items-center justify-center
        $html  = '<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="' . $nameSafe . '" role="dialog" aria-modal="true" aria-labelledby="' . $nameSafe . 'Label">';
        // Backdrop
        $html .= '<div class="absolute inset-0 bg-black/50" data-dismiss="modal" aria-hidden="true"></div>';
        // Dialog panel
        $html .= '<div class="relative bg-white rounded-xl shadow-xl w-full max-w-lg mx-4 overflow-hidden">';
        // Header
        $html .= '<div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">';
        $html .= '<h5 class="text-base font-semibold text-gray-900" id="' . $nameSafe . 'Label">' . $titleSafe . '</h5>';
        $html .= '<button type="button" class="ml-auto inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-300" data-dismiss="modal" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
        $html .= '</div>';
        // Body
        $html .= '<div class="px-6 py-4">' . $body . '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // ── Table Methods ─────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind filter modal for table search.
     * Uses Tailwind utility classes throughout.
     * No Bootstrap-specific attributes or classes.
     *
     * Requirements: 8.4
     *
     * @throws \InvalidArgumentException If name is empty.
     */
    public function renderFilterModal(
        string $name,
        string $title,
        array $elements
    ): string {
        if (empty($name)) {
            throw new \InvalidArgumentException('Name must be a non-empty string');
        }

        try {
            $name_safe  = $this->escapeHtml($name);
            $title_safe = $this->escapeHtml($title);
            $buttonID   = str_replace('_CanvaStackFILTERmodalBOX', '_submitFilterButton', $name_safe);

            // Modal body
            $html  = '<div class="px-6 py-4">';
            $html .= '<div id="' . $name_safe . '">';
            $html .= implode('', $elements);
            $html .= '</div>';
            $html .= '</div>';

            // Modal footer — Tailwind: flex justify-end gap-2 px-6 py-4 border-t
            $html .= '<div class="flex items-center justify-end gap-2 px-6 py-4 border-t border-gray-200">';
            $html .= '<div class="canvastack-action-box flex items-center gap-2 ml-auto">';

            // Export CSV button — hidden by default (Tailwind: hidden)
            $html .= '<button id="exportFilterButton' . $name_safe . '" class="hidden inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-sky-600 text-white hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 transition-colors btn-export-csv" type="button">Export to CSV</button>';

            // Submit filter button
            $html .= '<button id="' . $buttonID . '" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors" type="submit">';
            $html .= '<i class="fa fa-filter" aria-hidden="true"></i> &nbsp; Filter Data ' . $title_safe;
            $html .= '</button>';

            // Cancel button — data-dismiss handled by custom JS
            $html .= '<button type="reset" id="' . $name_safe . '-cancel" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors" data-dismiss="modal">Cancel</button>';

            $html .= '</div>';
            $html .= '</div>';

            return $html;

        } catch (\InvalidArgumentException $e) {
            error_log('TailwindAdapter::renderFilterModal() validation error: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('TailwindAdapter::renderFilterModal() error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * {@inheritdoc}
     *
     * Renders Tailwind action buttons for a table row.
     * Replaces Bootstrap-specific classes (`btn`, `btn-xs`, `btn-success`,
     * `btn-primary`, `btn-danger`, `btn-warning`, `dropdown-toggle`, etc.)
     * with Tailwind utility classes.
     *
     * The desktop/mobile responsive split is preserved using Tailwind
     * responsive prefixes (`sm:hidden`, `hidden sm:flex`) instead of
     * Bootstrap's `hidden-sm hidden-xs` / `hidden-md hidden-lg` classes.
     *
     * Requirements: 10.4
     */
    public function renderActionButtons(
        object $rowData,
        string $fieldTarget,
        string $currentUrl,
        mixed $action,
        ?array $removedButtons
    ): string {
        // Get the base output from DefaultAdapter (Bootstrap 4 HTML)
        $defaultAdapter = new \Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter();
        $html = $defaultAdapter->renderActionButtons($rowData, $fieldTarget, $currentUrl, $action, $removedButtons);

        // ── Replace Bootstrap button classes with Tailwind ────────────────
        // Use regex to handle classes with additional modifiers (like btn_view, btn_edit, btn_delete)
        
        // View button: btn btn-success btn-xs btn_view → Tailwind green button
        $html = preg_replace(
            '/class="btn btn-success btn-xs btn_view"/',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-colors btn_view"',
            $html
        );
        
        // Edit button: btn btn-primary btn-xs btn_edit → Tailwind blue button
        $html = preg_replace(
            '/class="btn btn-primary btn-xs btn_edit"/',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors btn_edit"',
            $html
        );
        
        // Delete button: btn btn-danger btn-xs → Tailwind red button
        $html = preg_replace(
            '/class="btn btn-danger btn-xs"/',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors"',
            $html
        );
        
        // Restore button: btn btn-warning btn-xs → Tailwind yellow button
        $html = preg_replace(
            '/class="btn btn-warning btn-xs"/',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 transition-colors"',
            $html
        );
        
        // Disabled buttons: btn btn-default btn-xs → Tailwind gray button
        $html = preg_replace(
            '/class="btn btn-default btn-xs([^"]*)"/',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-600 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors$1"',
            $html
        );
        
        // Additional action buttons: btn {custom-class} btn-{color} btn-xs → Tailwind button
        $html = preg_replace(
            '/class="btn ([a-zA-Z0-9_-]+) btn-([a-z]+) btn-xs"/',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-white bg-$2-600 hover:bg-$2-700 focus:outline-none focus:ring-2 focus:ring-$2-500 transition-colors $1"',
            $html
        );
        
        // Generic btn btn-xs (fallback)
        $html = preg_replace(
            '/class="btn btn-xs"/',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-300 transition-colors"',
            $html
        );
        
        // Delete form wrapper: btn btn_delete → inline-block btn_delete
        $html = str_replace('class="btn btn_delete"', 'class="inline-block btn_delete"', $html);
        
        // Dropdown toggle (mobile): btn btn-minier btn-yellow dropdown-toggle
        $html = str_replace(
            'class="btn btn-minier btn-yellow dropdown-toggle"',
            'class="inline-flex items-center justify-center w-7 h-7 rounded text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none transition-colors"',
            $html
        );
        
        // ── Replace responsive visibility classes ─────────────────────────
        $html = str_replace('class="hidden-sm hidden-xs action-buttons"', 'class="hidden sm:flex items-center gap-1 action-buttons"', $html);
        $html = str_replace('class="hidden-md hidden-lg"', 'class="flex sm:hidden"', $html);
        
        // ── Replace dropdown menu classes ─────────────────────────────────
        $html = str_replace(
            'class="dropdown-menu dropdown-only-icon dropdown-yellow dropdown-menu-right dropdown-caret dropdown-close"',
            'class="absolute right-0 mt-1 w-36 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-10"',
            $html
        );
        
        // ── Remove Bootstrap tooltip attributes ───────────────────────────
        $html = preg_replace('/\s+data-toggle="tooltip"/', '', $html);
        $html = preg_replace('/\s+data-placement="[^"]*"/', '', $html);

        return $html;
    }

    // ── Private Helpers ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Tailwind: `<div class="w-full"><div class="flex border-b border-gray-200" role="tablist">{$headersHtml}</div><div class="tab-content pt-4">{$contentsHtml}</div></div>`
     * No Bootstrap-specific classes (`tabbable`, `nav`, `nav-tabs`).
     *
     * Requirements: 6.1, 7.5
     */
    public function renderTabWrapper(string $headersHtml, string $contentsHtml): string
    {
        $html  = '<div class="w-full">';
        $html .= '<div class="flex border-b border-gray-200" role="tablist">';
        $html .= $headersHtml;
        $html .= '</div>';
        $html .= '<div class="tab-content pt-4">';
        $html .= $contentsHtml;
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * {@inheritdoc}
     *
     * Tailwind: `<div class="flex items-center gap-2">{$inputHtml}{$labelHtml}</div>`
     * No Bootstrap-specific classes (`ckbox`, `col-sm-3`, `form-check`).
     *
     * Requirements: 6.4, 7.4
     */
    public function renderCheckboxWrapper(string $checkboxType, string $inputHtml, string $labelHtml): string
    {
        return '<div class="flex items-center gap-2">' . $inputHtml . $labelHtml . '</div>';
    }

    // ── Grid System Methods ───────────────────────────────────────────────

    /**
     * Return the CSS class for grid container element.
     *
     * Tailwind: `'container mx-auto'`
     *
     * Requirements: 9.3
     */
    public function getContainerClass(): string
    {
        return 'container mx-auto';
    }

    /**
     * Return the CSS class for grid row element.
     *
     * Tailwind: `'flex flex-wrap'`
     *
     * Requirements: 9.3
     */
    public function getRowClass(): string
    {
        return 'flex flex-wrap';
    }

    /**
     * Return the CSS class for grid column element based on column count.
     *
     * Tailwind: Width classes based on 12-column grid system.
     * Maps Bootstrap column numbers to Tailwind width utilities.
     *
     * Requirements: 9.3
     *
     * @param int $columns Number of columns (1-12 in grid system).
     * @return string CSS class string for column.
     */
    public function getColumnClass(int $columns): string
    {
        // Map Bootstrap column numbers to Tailwind width classes
        // Bootstrap uses 12-column grid, so col-6 = 50%, col-4 = 33.33%, etc.
        $widthMap = [
            1  => 'w-1/12',
            2  => 'w-2/12',
            3  => 'w-3/12',
            4  => 'w-4/12',
            5  => 'w-5/12',
            6  => 'w-6/12',
            7  => 'w-7/12',
            8  => 'w-8/12',
            9  => 'w-9/12',
            10 => 'w-10/12',
            11 => 'w-11/12',
            12 => 'w-full',
        ];

        return $widthMap[$columns] ?? 'w-full';
    }

    // ── Template Helper Methods ───────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind breadcrumb navigation.
     * Uses Tailwind utility classes: `flex`, `items-center`, `space-x-2`, `text-sm`.
     * Replaces breadcrumb structure with Tailwind-appropriate markup.
     *
     * Requirements: 9.5
     */
    public function renderBreadcrumb(
        string $title,
        array $links,
        string|false $iconTitle,
        array|false $iconLinks,
        string|false $type
    ): string {
        // Security: Escape title
        $titleEscaped = $this->escapeHtml($title);

        if ('blankon' === $type) {
            // Legacy 'blankon' type breadcrumb with Tailwind classes
            $n = 0;
            $linkIcons = false;
            if (false !== $iconLinks) {
                foreach ($iconLinks as $link_icon) {
                    $linkIcons[] = "<i class=\"fa fa-{$this->escapeHtml($link_icon)}\"></i> ";
                }
            }

            $o  = "<div class=\"px-4 py-3 bg-white border-b border-gray-200\">";
            $o .= "<h4 class=\"text-lg font-semibold text-gray-800\">";
            if (false !== $iconTitle) {
                $o .= "<i class=\"fa fa-{$this->escapeHtml($iconTitle)}\"></i> ";
            }
            $o .= $titleEscaped;
            $o .= "</h4>";
            $o .= "<div class=\"hidden sm:block mt-2\">";
            if ($links) {
                $o .= "<ol class=\"flex items-center space-x-2 text-sm\">";
                foreach ($links as $link_title => $link_url) {
                    $n++;

                    $index      = $n - 1;
                    $linkTitle  = $this->escapeHtml(canvastack_underscore_to_camelcase($link_title));

                    $o .= "<li class=\"flex items-center\">";
                    if ($linkIcons && isset($linkIcons[$index])) {
                        $o .= $linkIcons[$index];
                    }
                    if (0 !== $link_title) {
                        $linkUrlEscaped = $this->escapeHtml($link_url);
                        $o .= "<a href=\"{$linkUrlEscaped}\" class=\"text-blue-600 hover:text-blue-800 transition-colors\">{$linkTitle}</a>";
                    } else {
                        $linkTitle = ucwords($this->escapeHtml($link_url));
                        $o .= "<span class=\"text-gray-600\">{$linkTitle}</span>";
                    }
                    $o .= "<i class=\"fa fa-angle-right ml-2 text-gray-400\"></i>";
                    $o .= "</li>";
                }
                $o .= "</ol>";
            }
            $o .= "</div>";
            $o .= "</div>";
        } else {
            // Default type breadcrumb with Tailwind classes
            // Tailwind: flex, items-center, justify-between, space-x-2, text-sm
            $o  = "<div class=\"flex items-center justify-between\">";
            $o .= "<div class=\"w-full\">";
            $o .= "<div class=\"flex items-center justify-between py-3\">";
            $o .= "<h4 class=\"text-lg font-semibold text-gray-800\">{$titleEscaped}</h4>";

            $n = 0;
            $linkIcons = false;
            if (false !== $iconLinks) {
                foreach ($iconLinks as $link_icon) {
                    $linkIcons[] = "<i class=\"fa fa-{$this->escapeHtml($link_icon)}\"></i> ";
                }
            }

            if ($links) {
                $o .= "<ol class=\"flex items-center space-x-2 text-sm ml-auto\">";
                foreach ($links as $link_title => $link_url) {
                    $n++;

                    $index     = $n - 1;
                    $linkTitle = $this->escapeHtml(canvastack_underscore_to_camelcase($link_title));

                    $o .= "<li class=\"flex items-center\">";
                    if (0 !== $link_title) {
                        $linkUrlEscaped = $this->escapeHtml($link_url);
                        $o .= "<a href=\"{$linkUrlEscaped}\" class=\"text-blue-600 hover:text-blue-800 transition-colors\">{$linkTitle}</a>";
                        // Add separator after link (except for last item)
                        $o .= "<span class=\"mx-2 text-gray-400\">/</span>";
                    } else {
                        $linkTitle = ucwords($this->escapeHtml($link_url));
                        $o .= "<span class=\"text-gray-600\">{$linkTitle}</span>";
                    }
                    $o .= "</li>";
                }
                $o .= "</ol>";
            }

            $o .= "</div></div></div>";
        }

        return $o;
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Tailwind sidebar content element.
     * Uses Tailwind utility classes: `flex`, `items-center`, `gap-3`.
     * No Bootstrap-specific classes.
     *
     * Requirements: 9.7
     */
    public function renderSidebarContent(
        string $mediaTitle,
        string|false $mediaHeading,
        string|false $mediaSubHeading,
        bool $type
    ): string {
        $base_url = canvastack_config('baseURL');
        
        if (false === $type) {
            // Simple sidebar type with Tailwind classes
            $mediaHeadingHtml    = false;
            $mediaSubHeadingHtml = false;
            
            if (false !== $mediaHeading) {
                $mediaHeadingHtml = "<h4 class=\"text-base font-semibold text-gray-800\">{$this->escapeHtml($mediaHeading)}</h4>";
            }
            if (false !== $mediaSubHeading) {
                $mediaSubHeadingHtml = "<small class=\"text-sm text-gray-600\">{$this->escapeHtml($mediaSubHeading)}</small>";
            }
            
            $o  = "<div class=\"sidebar-content p-4\">";
            $o .= "<div class=\"flex items-start gap-3\">";
            $o .= "{$mediaTitle}";
            $o .= "<div class=\"flex-1\">";
            $o .= "{$mediaHeadingHtml}";
            $o .= "{$mediaSubHeadingHtml}";
            $o .= "</div>";
            $o .= "</div>";
            $o .= "</div>";
        } else {
            // User panel type with Tailwind classes
            $sessions = canvastack_sessions();
            $userId = $sessions['id'] ?? 0; // Get user ID with fallback to 0
            
            $o  = "<div class=\"relative\">";
            // Tailwind: data-toggle handled by custom JS
            $o .= "<a data-toggle=\"collapse\" href=\"#userInfoBox\" role=\"button\" aria-expanded=\"false\" aria-controls=\"userInfoBox\" class=\"absolute top-2 right-2 inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-600 text-white hover:bg-blue-700 shadow-lg transition-colors\"><i class=\"ti-settings\"></i></a>";
            $o .= "<div class=\"user-panel p-4 bg-white rounded-lg shadow\">";
            $o .= "{$mediaTitle}";
            $o .= "<div class=\"multi-collapse hidden\" id=\"userInfoBox\">";
            $o .= "<div class=\"mt-3 space-y-1 rounded-lg shadow-md overflow-hidden\">";
            $o .= "<a href=\"{$base_url}/system/accounts/user/{$userId}\" class=\"flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors\">";
            $o .= "<i class=\"ti-user text-blue-600\"></i>Profile";
            $o .= "</a>";
            $o .= "<a href=\"{$base_url}/system/accounts/user/{$userId}/edit\" class=\"flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors\"><i class=\"ti-settings text-yellow-500\"></i>Edit</a>";
            $o .= "<a href=\"{$base_url}/logout\" class=\"flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition-colors\"><i class=\"ti-panel text-purple-600\"></i>Log Out</a>";
            $o .= "</div>";
            $o .= "</div>";
            $o .= "</div>";
            $o .= "</div>";
        }
        
        return $o;
    }

    /**
     * Escape a value for safe HTML output.
     *
     * @param mixed $value
     * @return string
     */
    private function escapeHtml(mixed $value): string
    {
        if (is_null($value) || false === $value) {
            return '';
        }

        if (is_array($value)) {
            return '';
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                $value = (string) $value;
            } else {
                return '';
            }
        }

        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Merge a CSS class into an attributes array.
     *
     * @param array        $attribute Existing attributes array.
     * @param string|false $key       Attribute key to add/update.
     * @param mixed        $value     Attribute value to set.
     * @return array Updated attributes array.
     */
    private function changeInputAttribute(array $attribute, string|false $key = false, mixed $value = false): array
    {
        if (false === $key) {
            return $attribute;
        }

        $new_attribute = [$key => $value];
        $attributes    = array_merge_recursive($attribute, $new_attribute);

        $_values = $attributes[$key] ?? null;

        if (is_array($_values)) {
            $values = implode(' ', $_values);
        } else {
            $values = $_values ?? '';
        }

        $_attribute = [$key => $values];
        return array_merge($attribute, $_attribute);
    }
}
