<?php

namespace Canvastack\Canvastack\Library\Theme\Adapters;

use Collective\Html\FormFacade;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;

/**
 * Bootstrap5Adapter — Bootstrap 5 Theme Adapter
 *
 * Generates HTML using Bootstrap 5 classes and attributes for the `canvasign`
 * template. Key differences from DefaultAdapter (Bootstrap 4):
 *
 * - Uses `data-bs-toggle` / `data-bs-dismiss` instead of `data-toggle` / `data-dismiss`
 * - Alert class is `alert alert-{type}` without `alert-block`
 * - Select uses native `form-select` instead of Chosen.js classes
 * - Checkbox uses `form-check`, `form-check-input`, `form-check-label`
 * - Hide class is `d-none` instead of `hide`
 * - Float-right class is `float-end` instead of `pull-right`
 * - Action buttons use `btn-sm` instead of `btn-xs`
 * - Table class omits `animated` and `fadeIn`
 *
 * @package    Canvastack\Canvastack\Library\Theme\Adapters
 * @author     wisnuwidi@canvastack.com
 * @copyright  Canvastack
 * @see        ThemeAdapterInterface
 */
class Bootstrap5Adapter implements ThemeAdapterInterface
{
    // ── Utility Methods ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     * Bootstrap 5: 'data-bs-toggle'
     */
    public function getDataToggleAttribute(): string
    {
        return 'data-bs-toggle';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 5: 'data-bs-dismiss'
     */
    public function getDismissAttribute(): string
    {
        return 'data-bs-dismiss';
    }

    /**
     * Render page-level action buttons — Bootstrap 5 output.
     *
     * Produces a flat list of <a> / <button> / <form> / <div class="dropdown">
     * elements using Bootstrap 5 classes. Values in $route_info->action_page
     * are already htmlspecialchars-escaped by the controller — do NOT
     * double-escape them.
     *
     * Key format: "color|Label"  or  "color|Label|dropdown" for dropdowns.
     */
    public function renderPageActionButtons(object $route_info): string
    {
        if (empty($route_info->action_page)) {
            return '';
        }

        $html = '';

        foreach ($route_info->action_page as $key => $value) {
            // Key format: "color|Label"  OR  "color|Label|dropdown"
            $parts = explode('|', $key);
            $color = $parts[0] ?? 'secondary';

            // Strip trailing "|dropdown" or "|disabled" suffixes from label
            $labelParts = array_slice($parts, 1);
            $labelParts = array_filter($labelParts, function($p) {
                return !in_array(strtolower($p), ['dropdown', 'disabled']);
            });
            $label = ucwords(implode('|', $labelParts));

            // Bootstrap 4 → Bootstrap 5 color aliases
            $colorMap = ['default' => 'secondary', 'inverse' => 'dark'];
            $color = $colorMap[$color] ?? $color;

            $isDelete  = str_contains(strtolower($label), 'delete');
            $isRestore = str_contains(strtolower($label), 'restore');

            if (is_array($value)) {
                // ── Dropdown button ──────────────────────────────────────
                $html .= '<div class="dropdown">';
                $html .= "<button type=\"button\" class=\"btn btn-{$color} btn-sm dropdown-toggle action-btn\" data-bs-toggle=\"dropdown\" aria-expanded=\"false\">{$label}</button>";
                $html .= '<ul class="dropdown-menu dropdown-menu-end">';

                foreach ($value as $item) {
                    if (!empty($item['divider'])) {
                        $html .= '<li><hr class="dropdown-divider"></li>';
                        continue;
                    }
                    $itemLabel = $item['label'] ?? '';
                    $itemUrl   = $item['url']   ?? '#';
                    $itemIcon  = $item['icon']  ?? '';

                    // Build data-* attributes — values are NOT escaped by View.php, escape here
                    $dataAttrs = '';
                    foreach ($item['data'] ?? [] as $dk => $dv) {
                        $ek = htmlspecialchars($dk, ENT_QUOTES, 'UTF-8');
                        $ev = htmlspecialchars($dv, ENT_QUOTES, 'UTF-8');
                        $dataAttrs .= " data-{$ek}=\"{$ev}\"";
                    }

                    $iconHtml = $itemIcon ? "<i class=\"{$itemIcon} me-1\"></i>" : '';
                    $html .= "<li><a class=\"dropdown-item cache-action\" href=\"{$itemUrl}\"{$dataAttrs}>{$iconHtml}{$itemLabel}</a></li>";
                }

                $html .= '</ul></div>';

            } elseif ($isDelete || $isRestore) {
                // ── Delete / Restore form ────────────────────────────────
                $decoded    = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
                $routeParts = explode('::', $decoded, 2);
                $routeName  = $routeParts[0] ?? '';
                $routeId    = (int) ($routeParts[1] ?? 0);
                $actionUrl  = route($routeName, $routeId);

                $html .= "<form method=\"POST\" action=\"{$actionUrl}\" onsubmit=\"return confirm('Are you sure?')\" style=\"display:inline\">";
                $html .= csrf_field();
                $html .= method_field('DELETE');
                $html .= "<button type=\"submit\" class=\"btn btn-{$color} btn-sm action-btn\">{$label}</button>";
                $html .= '</form>';

            } else {
                // ── Regular link button ──────────────────────────────────
                $html .= "<a href=\"{$value}\" class=\"btn btn-{$color} btn-sm action-btn\">{$label}</a>";
            }
        }

        return $html;
    }

    /**
     * {@inheritdoc}
     * Bootstrap 5: 'd-none'
     */
    public function getHideClass(): string
    {
        return 'd-none';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 5: 'float-end'
     */
    public function getFloatRightClass(): string
    {
        return 'float-end';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 5: 'float-start'
     */
    public function getFloatLeftClass(): string
    {
        return 'float-start';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 5 native select: 'form-select'
     */
    public function getSelectBoxClass(): string
    {
        return 'form-select';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 5 DataTable class string — omits `animated` and `fadeIn`
     * (Bootstrap 5 uses custom CSS for animations instead).
     */
    public function getTableClass(): string
    {
        return 'CanvaStack-table table table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap';
    }

    // ── Form Methods ──────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 tab header nav-item.
     * Uses `data-bs-toggle="tab"` instead of Bootstrap 4's `data-toggle="tab"`.
     *
     * Requirements: 5.1
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

        $activeClass  = false;
        $classTag     = false;
        $tabName      = ucwords(str_replace('_', ' ', $dataEscaped));
        $ariaSelected = 'false';

        if ($active) {
            $activeClass  = '' . $activeEscaped . '';
            $ariaSelected = 'true';
        }
        if ($class) {
            $classTag = '<i class="' . $classEscaped . '" aria-hidden="true"></i>';
        }

        // Bootstrap 5: data-bs-toggle="tab" instead of data-toggle="tab"
        $string = "<li class=\"nav-item\" role=\"presentation\"><a id=\"{$pointerEscaped}-tab\" class=\"nav-link {$activeClass}\" data-bs-toggle=\"tab\" role=\"tab\" href=\"#{$pointerEscaped}\" aria-selected=\"{$ariaSelected}\" aria-controls=\"{$pointerEscaped}\">{$classTag}{$tabName}</a></li>";

        return SafeHtml::mark($string);
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 tab content pane.
     * Structure is identical to Bootstrap 4 — the tab-pane / fade classes
     * are the same in Bootstrap 5.
     */
    public function renderTabContent(
        string $data,
        string $pointer,
        bool $active
    ): string {
        $pointerEscaped = $this->escapeHtml($pointer);

        $activeClass = false;
        $ariaHidden  = 'true';
        if (false !== $active) {
            $activeClass = " active show";
            $ariaHidden  = 'false';
        }

        $string = "<div id=\"{$pointerEscaped}\" class=\"tab-pane fade{$activeClass}\" role=\"tabpanel\" aria-hidden=\"{$ariaHidden}\" aria-labelledby=\"{$pointerEscaped}-tab\">{$data}</div>";

        return SafeHtml::mark($string);
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 dismissable alert.
     * Key differences from Bootstrap 4 (DefaultAdapter):
     * - Omits `alert-block` class (not used in Bootstrap 5)
     * - Uses `data-bs-dismiss="alert"` instead of `data-dismiss="alert"`
     * - Uses `btn-close` button instead of `<button class="close">`
     *
     * Requirements: 5.2, 5.3
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

        $content_message = null;
        if (is_array($message) && 'success' !== $type) {
            $content_message = '<ul class="alert-info-content">';
            foreach ($message as $mfield => $mData) {
                $mfieldEscaped = $this->escapeHtml($mfield);
                $mfieldLabel   = ucwords(str_replace('_', ' ', $mfieldEscaped));

                $content_message .= '<li class="title"><div>';
                $content_message .= '<label for="' . $mfieldEscaped . '" class="form-label">' . $mfieldLabel . '</label>';
                if (is_array($mData)) {
                    $content_message .= '<ul class="content">';
                    foreach ($mData as $imData) {
                        $imDataEscaped    = $this->escapeHtml($imData);
                        $content_message .= '<li>';
                        $content_message .= '<label for="' . $mfieldEscaped . '" class="form-label">' . $imDataEscaped . '</label>';
                        $content_message .= '</li>';
                    }
                    $content_message .= '</ul>';
                } else {
                    $mDataEscaped     = $this->escapeHtml($mData);
                    $content_message .= '<ul class="content"><li><label for="' . $mfieldEscaped . '" class="form-label">' . $mDataEscaped . '</label></li></ul>';
                }
                $content_message .= '</div></li>';
            }
            $content_message .= '</ul>';
        } else {
            if (!is_array($message)) {
                $content_message = $this->escapeHtml($message);
            }
        }

        $prefix_tag = false;
        if (false !== $prefix && '' !== $prefix) {
            $prefix_tag = "<strong><i class=\"fa {$prefix}\"></i> &nbsp;{$title}</strong>";
        }

        $extraHtml = '';
        if (false !== $extra && '' !== $extra) {
            $extraHtml = strip_tags($extra, '<br><b><i><strong><em><span><div><p><ul><li><a>');
        }

        $ariaLive = ($type === 'danger' || $type === 'warning') ? 'assertive' : 'polite';

        // Bootstrap 5: no `alert-block` class, uses `btn-close` + `data-bs-dismiss="alert"`
        $o  = "<div class=\"alert alert-{$type} alert-dismissible animated fadeInDown\" role=\"alert\" aria-live=\"{$ariaLive}\" aria-atomic=\"true\">";
        $o .= "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>";
        if (!is_array($message)) {
            $o .= "<p>{$prefix_tag} {$content_message}</p>";
        } else {
            $o .= "<p>{$prefix_tag}</p>{$content_message}";
        }
        $o .= $extraHtml;
        $o .= "</div>";

        return $o;
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 checkbox element.
     * Uses `form-check`, `form-check-input`, `form-check-label` structure
     * instead of Bootstrap 4's `ckbox ckbox-{class}` wrapper.
     *
     * Requirements: 5.7
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

        $nameAttr  = false;
        $valueAttr = false;
        $idAttr    = false;
        $idForAttr = false;
        $labelName = '&nbsp;';
        $checkBox  = false;

        if (false !== $name)  $nameAttr  = ' name="' . $nameEscaped . '"';
        if (false !== $value) $valueAttr = ' value="' . $valueEscaped . '"';

        if (false !== $id) {
            $idAttr    = ' id="' . $idEscaped . '"';
            $idForAttr = ' for="' . $idEscaped . '"';
        } else {
            $idAttr    = ' id="' . $nameEscaped . '"';
            $idForAttr = ' for="' . $nameEscaped . '"';
        }

        if (false !== $label) $labelName = "&nbsp; {$labelEscaped}";

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

            $inputNodeAttr = " " . $inputNode;
        }

        // Bootstrap 5: form-check wrapper with form-check-input / form-check-label
        // The $class modifier is appended to the wrapper for custom styling (e.g. form-check-{class})
        $o = "<div class=\"form-check form-check-{$classEscaped}\"><input class=\"form-check-input\" type=\"checkbox\"{$valueAttr}{$nameAttr}{$idAttr}{$checkBox}{$inputNodeAttr}><label class=\"form-check-label\"{$idForAttr}>{$labelName}</label></div>";

        return SafeHtml::mark($o);
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 select element.
     * Uses `form-select` class (native Bootstrap 5) instead of Chosen.js.
     *
     * Requirements: 5.4
     */
    public function renderSelectBox(
        string $name,
        array $values,
        mixed $selected,
        array $attributes,
        bool $label,
        array|bool $set_first_value
    ): string {
        $bs5SelectClass = $this->getSelectBoxClass(); // 'form-select'
        $default_attr   = ['class' => $bs5SelectClass];

        if (!empty($attributes)) {
            $attributes = $this->changeInputAttribute($attributes, 'class', $bs5SelectClass);
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
     * Renders a Bootstrap 5 modal wrapper.
     * Uses `data-bs-dismiss="modal"` and `data-bs-toggle="modal"`.
     * Modal structure follows Bootstrap 5 conventions.
     *
     * Requirements: 5.8
     */
    public function renderModalWrapper(
        string $name,
        string $title,
        array $elements
    ): string {
        $nameSafe  = $this->escapeHtml($name);
        $titleSafe = $this->escapeHtml($title);
        $body      = implode('', $elements);

        // Bootstrap 5: data-bs-dismiss, btn-close, modal-dialog-centered optional
        $html  = '<div class="modal fade" id="' . $nameSafe . '" tabindex="-1" aria-labelledby="' . $nameSafe . 'Label" aria-hidden="true">';
        $html .= '<div class="modal-dialog" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header">';
        $html .= '<h5 class="modal-title" id="' . $nameSafe . 'Label">' . $titleSafe . '</h5>';
        $html .= '<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>';
        $html .= '</div>';
        $html .= '<div class="modal-body">' . $body . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    // ── Table Methods ─────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 filter modal for table search.
     * Key differences from DefaultAdapter:
     * - Uses `data-bs-dismiss="modal"` instead of `data-dismiss="modal"`
     * - Uses `float-end` instead of `pull-right`
     * - Uses `d-none` instead of `hide`
     *
     * Requirements: 8.3
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

            $html  = '<div class="modal-body">';
            $html .= '<div id="' . $name_safe . '">';
            $html .= implode('', $elements);
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="modal-footer">';
            $html .= '<div class="canvastack-action-box">';
            // Bootstrap 5: data-bs-dismiss, float-end instead of pull-right
            $html .= '<button type="reset" id="' . $name_safe . '-cancel" class="btn btn-danger btn-slideright float-end" data-bs-dismiss="modal">Cancel</button>';
            $html .= '<button id="' . $buttonID . '" class="btn btn-primary btn-slideright float-end" type="submit">';
            $html .= '<i class="fa fa-filter"></i> &nbsp; Filter Data ' . $title_safe;
            $html .= '</button>';
            // Bootstrap 5: d-none instead of hide
            $html .= '<button id="exportFilterButton' . $name_safe . '" class="btn btn-info btn-slideright float-end btn-export-csv d-none" type="button">Export to CSV</button>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;

        } catch (\InvalidArgumentException $e) {
            error_log('Bootstrap5Adapter::renderFilterModal() validation error: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('Bootstrap5Adapter::renderFilterModal() error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * {@inheritdoc}
     *
     * Renders Bootstrap 5 action buttons for a table row.
     * Uses `btn btn-sm` instead of Bootstrap 4's `btn btn-xs`
     * (Bootstrap 5 removed the `btn-xs` size class).
     *
     * Delegates to DefaultAdapter and replaces `btn-xs` with `btn-sm`.
     *
     * Requirements: 10.3
     */
    public function renderActionButtons(
        object $rowData,
        string $fieldTarget,
        string $currentUrl,
        mixed $action,
        ?array $removedButtons
    ): string {
        // Get the base output from DefaultAdapter (which uses Bootstrap 4 btn-xs)
        $defaultAdapter = new \Canvastack\Canvastack\Library\Theme\Adapters\DefaultAdapter();
        $html = $defaultAdapter->renderActionButtons($rowData, $fieldTarget, $currentUrl, $action, $removedButtons);

        // Replace Bootstrap 4 btn-xs with Bootstrap 5 btn-sm
        return str_replace('btn-xs', 'btn-sm', $html);
    }

    // ── Private Helpers ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Bootstrap 5: same structure as DefaultAdapter — Bootstrap 5 nav-tabs is compatible.
     * `<div class="tabbable"><ul class="nav nav-tabs" role="tablist">{$headersHtml}</ul><div class="tab-content">{$contentsHtml}</div></div><br />`
     *
     * Requirements: 5.1, 7.5
     */
    public function renderTabWrapper(string $headersHtml, string $contentsHtml): string
    {
        $html  = '<div class="tabbable">';
        $html .= '<ul class="nav nav-tabs" role="tablist">';
        $html .= $headersHtml;
        $html .= '</ul>';
        $html .= '<div class="tab-content">';
        $html .= $contentsHtml;
        $html .= '</div>';
        $html .= '</div><br />';

        return $html;
    }

    /**
     * {@inheritdoc}
     *
     * Bootstrap 5: `<div class="form-check">{$inputHtml}{$labelHtml}</div>`
     * No `ckbox`, no `col-sm-3` — Bootstrap 5 uses form-check wrapper.
     *
     * Requirements: 5.7, 7.4
     */
    public function renderCheckboxWrapper(string $checkboxType, string $inputHtml, string $labelHtml): string
    {
        return '<div class="form-check">' . $inputHtml . $labelHtml . '</div>';
    }

    // ── Grid System Methods ───────────────────────────────────────────────

    /**
     * Return the CSS class for grid container element.
     *
     * Bootstrap 5: `'container'` (same as Bootstrap 4)
     *
     * Requirements: 9.3
     */
    public function getContainerClass(): string
    {
        return 'container';
    }

    /**
     * Return the CSS class for grid row element.
     *
     * Bootstrap 5: `'row'` (same as Bootstrap 4)
     *
     * Requirements: 9.3
     */
    public function getRowClass(): string
    {
        return 'row';
    }

    /**
     * Return the CSS class for grid column element based on column count.
     *
     * Bootstrap 5: `"col-{$columns}"` (same as Bootstrap 4)
     *
     * Requirements: 9.3
     *
     * @param int $columns Number of columns (1-12 in Bootstrap grid system).
     * @return string CSS class string for column.
     */
    public function getColumnClass(int $columns): string
    {
        return "col-{$columns}";
    }

    // ── Template Helper Methods ───────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 breadcrumb navigation.
     * Key differences from DefaultAdapter:
     * - Uses `float-end` instead of `pull-right`
     * - Uses `float-start` instead of `pull-left`
     * - Bootstrap 5 breadcrumb classes are mostly compatible with BS4
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
            // Legacy 'blankon' type breadcrumb
            $n = 0;
            $linkIcons = false;
            if (false !== $iconLinks) {
                foreach ($iconLinks as $link_icon) {
                    $linkIcons[] = "<i class=\"fa fa-{$this->escapeHtml($link_icon)}\"></i> ";
                }
            }

            $o  = "<div class=\"header-content\">";
            $o .= "<h4 style=\"margin:3px 6px !important\">";
            if (false !== $iconTitle) {
                $o .= "<i class=\"fa fa-{$this->escapeHtml($iconTitle)}\"></i> ";
            }
            $o .= $titleEscaped;
            $o .= "</h4>";
            $o .= "<div class=\"breadcrumb-wrapper hidden-xs\">";
            if ($links) {
                $o .= "<ol class=\"breadcrumb\">";
                foreach ($links as $link_title => $link_url) {
                    $n++;

                    $index      = $n - 1;
                    $linkTitle  = $this->escapeHtml(canvastack_underscore_to_camelcase($link_title));

                    $o .= "<li>";
                    if ($linkIcons && isset($linkIcons[$index])) {
                        $o .= $linkIcons[$index];
                    }
                    if (0 !== $link_title) {
                        $linkUrlEscaped = $this->escapeHtml($link_url);
                        $o .= "<a href=\"{$linkUrlEscaped}\">{$linkTitle}</a>";
                    } else {
                        $linkTitle = ucwords($this->escapeHtml($link_url));
                        $o .= "<a>{$linkTitle}</a>";
                    }
                    $o .= "<i class=\"fa fa-angle-right\"></i>";
                    $o .= "</li>";
                }
                $o .= "</ol>";
            }
            $o .= "</div>";
            $o .= "</div>";
        } else {
            // Default type breadcrumb (current standard)
            // Bootstrap 5: use float-end instead of pull-right, float-start instead of pull-left
            $o  = "<div class=\"row align-items-center\">";
            $o .= "<div class=\"col-sm-12\">";
            $o .= "<div class=\"breadcrumbs-area clearfix\">";
            $o .= "<h4 class=\"page-title float-start\">{$titleEscaped}</h4>";

            $n = 0;
            $linkIcons = false;
            if (false !== $iconLinks) {
                foreach ($iconLinks as $link_icon) {
                    $linkIcons[] = "<i class=\"fa fa-{$this->escapeHtml($link_icon)}\"></i> ";
                }
            }

            if ($links) {
                $o .= "<ul class=\"breadcrumbs float-end\">";
                foreach ($links as $link_title => $link_url) {
                    $n++;

                    $index     = $n - 1;
                    $linkTitle = $this->escapeHtml(canvastack_underscore_to_camelcase($link_title));

                    $o .= "<li>";
                    if (0 !== $link_title) {
                        $linkUrlEscaped = $this->escapeHtml($link_url);
                        $o .= "<a href=\"{$linkUrlEscaped}\">{$linkTitle}</a>";
                    } else {
                        $linkTitle = ucwords($this->escapeHtml($link_url));
                        $o .= "<span>{$linkTitle}</span>";
                    }
                    $o .= "</li>";
                }
                $o .= "</ul>";
            }

            $o .= "</div></div></div>";
        }

        return $o;
    }

    /**
     * {@inheritdoc}
     *
     * Renders a Bootstrap 5 sidebar content element.
     * Key differences from DefaultAdapter:
     * - Uses `float-start` instead of `pull-left`
     * - Bootstrap 5 media classes are mostly compatible with BS4
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
            // Simple sidebar type
            $mediaHeadingHtml    = false;
            $mediaSubHeadingHtml = false;
            
            if (false !== $mediaHeading) {
                $mediaHeadingHtml = "<h4 class=\"media-heading\">{$this->escapeHtml($mediaHeading)}</h4>";
            }
            if (false !== $mediaSubHeading) {
                $mediaSubHeadingHtml = "<small>{$this->escapeHtml($mediaSubHeading)}</small>";
            }
            
            $o  = "<div class=\"sidebar-content\">";
            $o .= "<div class=\"media\">";
            $o .= "{$mediaTitle}";
            $o .= "<div class=\"media-body\">";
            $o .= "{$mediaHeadingHtml}";
            $o .= "{$mediaSubHeadingHtml}";
            $o .= "</div>";
            $o .= "</div>";
            $o .= "</div>";
        } else {
            // User panel type
            $sessions = canvastack_sessions();
            $userId = $sessions['id'] ?? 0; // Get user ID with fallback to 0
            
            $o  = "<div class=\"relative\">";
            // Bootstrap 5: data-bs-toggle instead of data-toggle
            $o .= "<a data-bs-toggle=\"collapse\" href=\"#userInfoBox\" role=\"button\" aria-expanded=\"false\" aria-controls=\"userInfoBox\" class=\"btn-sets btn-sets-sm absolute sets-right-bottom sets-top btn-primary shadow1 collapsed\"><i class=\"ti-settings\"></i></a>";
            $o .= "<div class=\"user-panel light\">";
            $o .= "{$mediaTitle}";
            $o .= "<div class=\"multi-collapse collapse\" id=\"userInfoBox\">";
            $o .= "<div class=\"list-group mt-3 shadow\">";
            $o .= "<a href=\"{$base_url}/system/accounts/user/{$userId}\" class=\"list-group-item list-group-item-action \">";
            $o .= "<i class=\"me-2 ti-user text-blue\"></i>Profile";
            $o .= "</a>";
            $o .= "<a href=\"{$base_url}/system/accounts/user/{$userId}/edit\" class=\"list-group-item list-group-item-action\"><i class=\"me-2 ti-settings text-yellow\"></i>Edit</a>";
            $o .= "<a href=\"{$base_url}/logout\" class=\"list-group-item list-group-item-action\"><i class=\"me-2 ti-panel text-purple\"></i>Log Out</a>";
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
