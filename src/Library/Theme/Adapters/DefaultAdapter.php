<?php

namespace Canvastack\Canvastack\Library\Theme\Adapters;

use Collective\Html\FormFacade;
use Canvastack\Canvastack\Library\Constants\SafeHtml;
use Canvastack\Canvastack\Library\Constants\FormConstants;
use Canvastack\Canvastack\Library\Theme\ThemeAdapterInterface;

/**
 * DefaultAdapter — Bootstrap 4 Theme Adapter
 *
 * Replicates the existing Bootstrap 4 helper function output byte-for-byte.
 * This adapter is used for the `default` template and serves as the fallback
 * when no other adapter is registered for the active template.
 *
 * All render methods produce output that is identical to the corresponding
 * legacy helper functions in FormObject.php and Table.php.
 *
 * @package    Canvastack\Canvastack\Library\Theme\Adapters
 * @author     wisnuwidi@canvastack.com
 * @copyright  Canvastack
 * @see        ThemeAdapterInterface
 */
class DefaultAdapter implements ThemeAdapterInterface
{
    // ── Utility Methods ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     * Bootstrap 4: 'data-toggle'
     */
    public function getDataToggleAttribute(): string
    {
        return 'data-toggle';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 4: 'data-dismiss'
     */
    public function getDismissAttribute(): string
    {
        return 'data-dismiss';
    }

    /**
     * Render page-level action buttons — Bootstrap 4 output.
     * Delegates to the existing canvastack_action_buttons() helper.
     */
    public function renderPageActionButtons(object $route_info): string
    {
        return canvastack_action_buttons($route_info);
    }

    /**
     * {@inheritdoc}
     * Bootstrap 4: 'hide'
     */
    public function getHideClass(): string
    {
        return 'hide';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 4: 'pull-right'
     */
    public function getFloatRightClass(): string
    {
        return 'pull-right';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 4: 'pull-left'
     */
    public function getFloatLeftClass(): string
    {
        return 'pull-left';
    }

    /**
     * {@inheritdoc}
     * Bootstrap 4 / Chosen.js: 'chosen-select-deselect chosen-selectbox'
     */
    public function getSelectBoxClass(): string
    {
        return FormConstants::DEFAULT_SELECTBOX_CLASS;
    }

    /**
     * {@inheritdoc}
     * Returns the full Bootstrap 4 DataTable class string, identical to
     * the CANVASTACK_DEFAULT_TABLE_CLASS constant.
     */
    public function getTableClass(): string
    {
        return 'CanvaStack-table table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap';
    }

    // ── Form Methods ──────────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Produces output identical to canvastack_form_create_header_tab().
     * Uses Bootstrap 4 data-toggle="tab" attribute.
     */
    public function renderTabHeader(
        string $data,
        string $pointer,
        string|false $active,
        string|false $class
    ): string {
        // Security: Escape all user inputs (mirrors existing helper)
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

        // Accessibility: aria-selected, aria-controls, id for aria-labelledby reference
        $string = "<li class=\"nav-item\" role=\"presentation\"><a id=\"{$pointerEscaped}-tab\" class=\"nav-link {$activeClass}\" data-toggle=\"tab\" role=\"tab\" href=\"#{$pointerEscaped}\" aria-selected=\"{$ariaSelected}\" aria-controls=\"{$pointerEscaped}\">{$classTag}{$tabName}</a></li>";

        return SafeHtml::mark($string);
    }

    /**
     * {@inheritdoc}
     *
     * Produces output identical to canvastack_form_create_content_tab().
     */
    public function renderTabContent(
        string $data,
        string $pointer,
        bool $active
    ): string {
        // Security: Escape pointer to prevent XSS in ID attribute
        $pointerEscaped = $this->escapeHtml($pointer);

        $activeClass = false;
        $ariaHidden  = 'true';
        if (false !== $active) {
            $activeClass = " active show";
            $ariaHidden  = 'false';
        }

        // Note: $data is assumed to be safe HTML content (already processed)
        // Accessibility: aria-hidden and aria-labelledby
        $string = "<div id=\"{$pointerEscaped}\" class=\"tab-pane fade{$activeClass}\" role=\"tabpanel\" aria-hidden=\"{$ariaHidden}\" aria-labelledby=\"{$pointerEscaped}-tab\">{$data}</div>";

        return SafeHtml::mark($string);
    }

    /**
     * {@inheritdoc}
     *
     * Produces output identical to canvastack_form_alert_message().
     * Uses Bootstrap 4 pattern: alert-block class + data-dismiss="alert".
     */
    public function renderAlertMessage(
        string|array $message,
        string $type,
        string $title,
        string $prefix,
        string|false $extra
    ): string {
        // Security: Escape type and prefix for safe HTML rendering
        $type   = $this->escapeHtml($type);
        $prefix = $this->escapeHtml($prefix);
        $title  = $this->escapeHtml($title);

        $content_message = null;
        if (is_array($message) && 'success' !== $type) {
            $content_message = '<ul class="alert-info-content">';
            foreach ($message as $mfield => $mData) {
                // Security: Escape field names
                $mfieldEscaped = $this->escapeHtml($mfield);
                $mfieldLabel   = ucwords(str_replace('_', ' ', $mfieldEscaped));

                $content_message .= '<li class="title"><div>';
                $content_message .= '<label for="' . $mfieldEscaped . '" class="control-label">' . $mfieldLabel . '</label>';
                if (is_array($mData)) {
                    $content_message .= '<ul class="content">';
                    foreach ($mData as $imData) {
                        $imDataEscaped    = $this->escapeHtml($imData);
                        $content_message .= '<li>';
                        $content_message .= '<label for="' . $mfieldEscaped . '" class="control-label">' . $imDataEscaped . '</label>';
                        $content_message .= '</li>';
                    }
                    $content_message .= '</ul>';
                } else {
                    $mDataEscaped     = $this->escapeHtml($mData);
                    $content_message .= '<ul class="content"><li><label for="' . $mfieldEscaped . '" class="control-label">' . $mDataEscaped . '</label></li></ul>';
                }
                $content_message .= '</div></li>';
            }
            $content_message .= '</ul>';
        } else {
            // Security: Escape simple message string
            if (!is_array($message)) {
                $content_message = $this->escapeHtml($message);
            }
        }

        $prefix_tag = false;
        if (false !== $prefix && '' !== $prefix) {
            $prefix_tag = "<strong><i class=\"fa {$prefix}\"></i> &nbsp;{$title}</strong>";
        }

        // Security: Sanitize extra HTML — strip dangerous tags but allow safe formatting
        $extraHtml = '';
        if (false !== $extra && '' !== $extra) {
            $extraHtml = strip_tags($extra, '<br><b><i><strong><em><span><div><p><ul><li><a>');
        }

        // Accessibility: aria-live based on alert type
        $ariaLive = ($type === 'danger' || $type === 'warning') ? 'assertive' : 'polite';

        $o  = "<div class=\"alert alert-block alert-{$type} animated fadeInDown alert-dismissable\" role=\"alert\" aria-live=\"{$ariaLive}\" aria-atomic=\"true\">";
        $o .= "<button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close alert\">";
        $o .= "<i class=\"fa fa-times\" aria-hidden=\"true\"></i>";
        $o .= "</button>";
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
     * Produces output identical to canvastack_form_checkList().
     * Uses Bootstrap 4 / Ace Admin pattern: ckbox ckbox-{class} wrapper.
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
        // Security: Escape all output values
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

        // Security: Validate and sanitize inputNode (mirrors existing helper)
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

            if (!preg_match('/^[a-zA-Z0-9_-]+=["\'][^"\']*["\']$/', $inputNode)) {
                error_log('INFO: inputNode format unusual (may be valid): ' . $inputNode);
            }

            $inputNodeAttr = " " . $inputNode;
        }

        // Accessibility: role for checkbox wrapper
        $o = "<div class=\"ckbox ckbox-{$classEscaped}\" role=\"checkbox\" tabindex=\"0\"><input type=\"checkbox\"{$valueAttr}{$nameAttr}{$idAttr}{$checkBox}{$inputNodeAttr}><label{$idForAttr}>{$labelName}</label></div>";

        return SafeHtml::mark($o);
    }

    /**
     * {@inheritdoc}
     *
     * Produces output identical to canvastack_form_selectbox().
     * Uses Chosen.js class: chosen-select-deselect chosen-selectbox.
     */
    public function renderSelectBox(
        string $name,
        array $values,
        mixed $selected,
        array $attributes,
        bool $label,
        array|bool $set_first_value
    ): string {
        $default_attr = ['class' => FormConstants::DEFAULT_SELECTBOX_CLASS];

        if (!empty($attributes)) {
            $attributes = $this->changeInputAttribute($attributes, 'class', FormConstants::DEFAULT_SELECTBOX_CLASS);
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
     * Renders a Bootstrap 4 modal wrapper.
     * Uses data-dismiss="modal" and data-toggle="modal".
     */
    public function renderModalWrapper(
        string $name,
        string $title,
        array $elements
    ): string {
        $nameSafe  = $this->escapeHtml($name);
        $titleSafe = $this->escapeHtml($title);
        $body      = implode('', $elements);

        $html  = '<div class="modal fade" id="' . $nameSafe . '" tabindex="-1" role="dialog" aria-labelledby="' . $nameSafe . 'Label" aria-hidden="true">';
        $html .= '<div class="modal-dialog" role="document">';
        $html .= '<div class="modal-content">';
        $html .= '<div class="modal-header">';
        $html .= '<h4 class="modal-title" id="' . $nameSafe . 'Label">' . $titleSafe . '</h4>';
        $html .= '<button type="button" class="close" data-dismiss="modal" aria-label="Close">';
        $html .= '<span aria-hidden="true">&times;</span>';
        $html .= '</button>';
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
     * Produces output identical to canvastack_modal_content_html().
     * Uses Bootstrap 4 attributes: data-dismiss="modal", pull-right, hide.
     *
     * @throws \InvalidArgumentException If name is empty or parameters are invalid.
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
            $html .= '<button type="reset" id="' . $name_safe . '-cancel" class="btn btn-danger btn-slideright pull-right" data-dismiss="modal">Cancel</button>';
            $html .= '<button id="' . $buttonID . '" class="btn btn-primary btn-slideright pull-right" type="submit">';
            $html .= '<i class="fa fa-filter"></i> &nbsp; Filter Data ' . $title_safe;
            $html .= '</button>';
            $html .= '<button id="exportFilterButton' . $name_safe . '" class="btn btn-info btn-slideright pull-right btn-export-csv hide" type="button">Export to CSV</button>';
            $html .= '</div>';
            $html .= '</div>';

            return $html;

        } catch (\InvalidArgumentException $e) {
            error_log('DefaultAdapter::renderFilterModal() validation error: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('DefaultAdapter::renderFilterModal() error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * {@inheritdoc}
     *
     * Renders Bootstrap 4 action buttons for a table row.
     * This is the actual implementation that was originally in canvastack_table_action_button().
     * Uses `btn btn-xs` (Bootstrap 4 button size).
     *
     * Requirements: 10.2
     */
    public function renderActionButtons(
        object $rowData,
        string $fieldTarget,
        string $currentUrl,
        mixed $action,
        ?array $removedButtons
    ): string {
        // Check if actions are enabled
        if (!config('canvastack.datatables.actions.enabled', true)) {
            return '';
        }

        // Input validation
        if (!is_object($rowData)) {
            throw new \InvalidArgumentException('Row data must be an object');
        }

        if (!is_string($fieldTarget) || empty($fieldTarget)) {
            throw new \InvalidArgumentException('Field target must be a non-empty string');
        }

        if (!is_string($currentUrl) || empty($currentUrl)) {
            throw new \InvalidArgumentException('Current URL must be a non-empty string');
        }

        try {
            $enabledAction = canvastack_action_init_enabled_actions();

            // Check privileges if enabled
            if (config('canvastack.datatables.actions.check_privileges', true)) {
                $actions = canvastack_action_check_privileges($action);
            } else {
                $actions = is_array($action) ? $action : [$action];
            }

            $addActions = canvastack_action_parse_actions($action, $enabledAction);

            canvastack_action_process_removed_buttons($removedButtons, $actions, $enabledAction);

            $path = canvastack_action_build_paths($rowData, $fieldTarget, $currentUrl, $enabledAction);
            $add_path = canvastack_action_build_additional_paths($addActions, $currentUrl, $rowData, $fieldTarget);

            return create_action_buttons($path['view'], $path['edit'], $path['delete'], $add_path);

        } catch (\InvalidArgumentException $e) {
            error_log('DefaultAdapter::renderActionButtons() validation error: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            error_log('DefaultAdapter::renderActionButtons() error: ' . $e->getMessage());
            return ''; // Return empty string on error
        }
    }

    // ── Private Helpers ───────────────────────────────────────────────────

    /**
     * {@inheritdoc}
     *
     * Bootstrap 4: `<div class="tabbable"><ul class="nav nav-tabs" role="tablist">{$headersHtml}</ul><div class="tab-content">{$contentsHtml}</div></div><br />`
     *
     * Requirements: 4.2, 7.5
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
     * Bootstrap 4: `<div class="col-sm-3 ckbox{$checkboxType}">{$inputHtml}{$labelHtml}</div>`
     *
     * Requirements: 4.4, 7.4
     */
    public function renderCheckboxWrapper(string $checkboxType, string $inputHtml, string $labelHtml): string
    {
        return '<div class="col-sm-3 ' . FormConstants::CLASS_CKBOX . $checkboxType . '">' . $inputHtml . $labelHtml . '</div>';
    }

    // ── Grid System Methods ───────────────────────────────────────────────

    /**
     * Return the CSS class for grid container element.
     *
     * Bootstrap 4: `'container'`
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
     * Bootstrap 4: `'row'`
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
     * Bootstrap 4: `"col-{$columns}"`
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
     * Renders a Bootstrap 4 breadcrumb navigation.
     * Migrated from canvastack_breadcrumb() helper function.
     * Uses Bootstrap 4 classes: `breadcrumb-wrapper`, `breadcrumb`, `breadcrumbs-area`, `pull-right`.
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
            // Breadcrumb is now rendered inside header-area, so we don't need the outer wrapper
            // Just render the breadcrumbs content directly
            $o  = "<div class=\"row align-items-center\">";
            $o .= "<div class=\"col-sm-12\">";
            $o .= "<div class=\"breadcrumbs-area clearfix\">";
            $o .= "<h4 class=\"page-title pull-left\">{$titleEscaped}</h4>";

            $n = 0;
            $linkIcons = false;
            if (false !== $iconLinks) {
                foreach ($iconLinks as $link_icon) {
                    $linkIcons[] = "<i class=\"fa fa-{$this->escapeHtml($link_icon)}\"></i> ";
                }
            }

            if ($links) {
                $o .= "<ul class=\"breadcrumbs pull-right\">";
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
     * Renders a Bootstrap 4 sidebar content element.
     * Migrated from canvastack_sidebar_content() helper function.
     * Uses Bootstrap 4 classes: `sidebar-content`, `media`, `media-body`, `pull-left`.
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
            $o .= "<a data-toggle=\"collapse\" href=\"#userInfoBox\" role=\"button\" aria-expanded=\"false\" aria-controls=\"userInfoBox\" class=\"btn-sets btn-sets-sm absolute sets-right-bottom sets-top btn-primary shadow1 collapsed\"><i class=\"ti-settings\"></i></a>";
            $o .= "<div class=\"user-panel light\">";
            $o .= "{$mediaTitle}";
            $o .= "<div class=\"multi-collapse collapse\" id=\"userInfoBox\">";
            $o .= "<div class=\"list-group mt-3 shadow\">";
            $o .= "<a href=\"{$base_url}/system/accounts/user/{$userId}\" class=\"list-group-item list-group-item-action \">";
            $o .= "<i class=\"mr-2 ti-user text-blue\"></i>Profile";
            $o .= "</a>";
            $o .= "<a href=\"{$base_url}/system/accounts/user/{$userId}/edit\" class=\"list-group-item list-group-item-action\"><i class=\"mr-2 ti-settings text-yellow\"></i>Edit</a>";
            $o .= "<a href=\"{$base_url}/logout\" class=\"list-group-item list-group-item-action\"><i class=\"mr-2 ti-panel text-purple\"></i>Log Out</a>";
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
     * Mirrors canvastack_form_escape_html() to keep the adapter self-contained
     * without a hard dependency on the global helper.
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
            // Arrays are not expected here; return empty string safely.
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
     * Mirrors canvastack_form_change_input_attribute() to keep the adapter
     * self-contained.
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
