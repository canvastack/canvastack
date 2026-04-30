<?php

namespace Canvastack\Canvastack\Library\Theme;

interface ThemeAdapterInterface
{
    // ── Form Methods ──────────────────────────────────────────────────────

    /**
     * Render HTML tab header (nav-item + nav-link).
     *
     * @param string       $data    The tab identifier / data target value.
     * @param string       $pointer The tab pointer / href value.
     * @param string|false $active  The active tab identifier, or false if none.
     * @param string|false $class   Additional CSS class(es) to append, or false.
     *
     * @return string Rendered HTML string for the tab header element.
     */
    public function renderTabHeader(
        string $data,
        string $pointer,
        string|false $active,
        string|false $class
    ): string;

    /**
     * Render HTML tab content pane.
     *
     * @param string $data    The tab identifier that matches the corresponding tab header.
     * @param string $pointer The tab pointer value.
     * @param bool   $active  Whether this tab pane is the active/visible one.
     *
     * @return string Rendered HTML string for the tab content pane element.
     */
    public function renderTabContent(
        string $data,
        string $pointer,
        bool $active
    ): string;

    /**
     * Render HTML dismissable alert message.
     *
     * @param string|array $message The alert message text, or an array of messages.
     * @param string       $type    The alert type (e.g. 'success', 'danger', 'warning', 'info').
     * @param string       $title   The alert title displayed in bold.
     * @param string       $prefix  A prefix string prepended to the message.
     * @param string|false $extra   Extra HTML or attributes to include, or false.
     *
     * @return string Rendered HTML string for the alert element.
     *               Bootstrap 4 (DefaultAdapter): uses `alert-block` class and `data-dismiss="alert"`.
     *               Bootstrap 5 (Bootstrap5Adapter): omits `alert-block`, uses `data-bs-dismiss="alert"`.
     *               Tailwind (TailwindAdapter): uses utility classes such as `flex items-start gap-3 p-4 rounded-lg`.
     */
    public function renderAlertMessage(
        string|array $message,
        string $type,
        string $title,
        string $prefix,
        string|false $extra
    ): string;

    /**
     * Render HTML checkbox element.
     *
     * @param mixed        $name      The input name attribute.
     * @param string|false $value     The input value attribute, or false.
     * @param string|false $label     The label text, or false to omit the label.
     * @param bool         $checked   Whether the checkbox is checked by default.
     * @param string       $class     CSS class modifier for the checkbox wrapper.
     * @param string|false $id        The input id attribute, or false to omit.
     * @param string|null  $inputNode Additional HTML to inject inside the input node, or null.
     *
     * @return string Rendered HTML string for the checkbox element.
     *               Bootstrap 4 (DefaultAdapter): uses `ckbox ckbox-{class}` wrapper class.
     *               Bootstrap 5 (Bootstrap5Adapter): uses `form-check`, `form-check-input`, `form-check-label`.
     *               Tailwind (TailwindAdapter): uses utility classes such as `flex items-center gap-2`.
     */
    public function renderCheckList(
        mixed $name,
        string|false $value,
        string|false $label,
        bool $checked,
        string $class,
        string|false $id,
        ?string $inputNode
    ): string;

    /**
     * Render HTML select element.
     *
     * @param string       $name            The select input name attribute.
     * @param array        $values          Associative array of option values and labels.
     * @param mixed        $selected        The currently selected value.
     * @param array        $attributes      Additional HTML attributes for the select element.
     * @param bool         $label           Whether to render a label element.
     * @param array|bool   $set_first_value First option configuration, or false to omit.
     *
     * @return string Rendered HTML string for the select element.
     *               Bootstrap 4 (DefaultAdapter): uses `chosen-select-deselect chosen-selectbox` (Chosen.js).
     *               Bootstrap 5 (Bootstrap5Adapter): uses `form-select` (native Bootstrap 5 select).
     *               Tailwind (TailwindAdapter): uses `form-input` class.
     */
    public function renderSelectBox(
        string $name,
        array $values,
        mixed $selected,
        array $attributes,
        bool $label,
        array|bool $set_first_value
    ): string;

    /**
     * Render HTML modal container wrapper.
     *
     * @param string $name     The modal identifier used for targeting.
     * @param string $title    The modal title displayed in the header.
     * @param array  $elements Array of HTML element strings to render inside the modal body.
     *
     * @return string Rendered HTML string for the complete modal container.
     *               Bootstrap 4 (DefaultAdapter): uses `data-dismiss="modal"` and `data-toggle="modal"`.
     *               Bootstrap 5 (Bootstrap5Adapter): uses `data-bs-dismiss="modal"` and `data-bs-toggle="modal"`.
     *               Tailwind (TailwindAdapter): uses utility classes such as `fixed inset-0 z-50 flex items-center justify-center`.
     */
    public function renderModalWrapper(
        string $name,
        string $title,
        array $elements
    ): string;

    /**
     * Return the default CSS class string for select elements.
     *
     * @return string CSS class string appropriate for the active CSS framework.
     *               Bootstrap 4 (DefaultAdapter): `'chosen-select-deselect chosen-selectbox'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'form-select'`
     *               Tailwind (TailwindAdapter): `'form-input'`
     */
    public function getSelectBoxClass(): string;

    /**
     * Return the data-toggle attribute name appropriate for the active CSS framework.
     *
     * @return string The attribute name string.
     *               Bootstrap 4 (DefaultAdapter): `'data-toggle'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'data-bs-toggle'`
     *               Tailwind (TailwindAdapter): `'data-toggle'` (uses custom JS)
     */
    public function getDataToggleAttribute(): string;

    // ── Table Methods ─────────────────────────────────────────────────────

    /**
     * Render HTML modal for table filter search.
     *
     * @param string $name     The modal identifier used for targeting.
     * @param string $title    The modal title displayed in the header.
     * @param array  $elements Array of HTML element strings to render inside the filter modal body.
     *
     * @return string Rendered HTML string for the filter modal element.
     *               Bootstrap 4 (DefaultAdapter): uses `data-dismiss="modal"`, `pull-right`, and `hide` classes.
     *               Bootstrap 5 (Bootstrap5Adapter): uses `data-bs-dismiss="modal"`, `float-end`, and `d-none` classes.
     *               Tailwind (TailwindAdapter): uses utility classes such as `fixed inset-0 z-50 flex items-center justify-center`.
     */
    public function renderFilterModal(
        string $name,
        string $title,
        array $elements
    ): string;

    /**
     * Return the CSS class string for the DataTable element.
     *
     * @return string CSS class string appropriate for the active CSS framework.
     *               Bootstrap 4 (DefaultAdapter): `'CanvaStack-table table animated fadeIn table-striped table-default table-bordered table-hover dataTable repeater display responsive nowrap'`
     *               Bootstrap 5 (Bootstrap5Adapter): similar classes without `animated` and `fadeIn`.
     *               Tailwind (TailwindAdapter): utility classes such as `'w-full text-sm text-left'`.
     */
    public function getTableClass(): string;

    /**
     * Render HTML action buttons for a table row.
     *
     * @param object     $rowData        The data object for the current table row.
     * @param string     $fieldTarget    The field name used to identify the row target.
     * @param string     $currentUrl     The current page URL for constructing action links.
     * @param mixed      $action         Action configuration (array or string) defining available buttons.
     * @param array|null $removedButtons List of button identifiers to exclude, or null to include all.
     *
     * @return string Rendered HTML string for the action buttons group.
     *               Bootstrap 4 (DefaultAdapter): uses `btn btn-xs` and Bootstrap 4 button classes.
     *               Bootstrap 5 (Bootstrap5Adapter): uses `btn btn-sm` (Bootstrap 5 has no `btn-xs`).
     *               Tailwind (TailwindAdapter): uses Tailwind utility classes without Bootstrap-specific classes.
     */
    public function renderActionButtons(
        object $rowData,
        string $fieldTarget,
        string $currentUrl,
        mixed $action,
        ?array $removedButtons
    ): string;

    /**
     * Render page-level action buttons from $route_info->action_page.
     *
     * Unlike renderActionButtons() which handles DataTable row buttons,
     * this method renders the per-page CRUD / utility buttons (e.g. "Add",
     * "Back to List", "Delete", dropdown menus like "Cache").
     *
     * The $route_info->action_page values are already htmlspecialchars-escaped
     * by the canvastack controller. Implementations must NOT double-escape them.
     *
     * @param object $route_info  Route info object with action_page array property.
     * @return string             Safe HTML ready for {!! !!} output in Blade.
     *
     *               Bootstrap 4 (DefaultAdapter): Bootstrap 4 classes, data-toggle, pull-right.
     *               Bootstrap 5 (Bootstrap5Adapter): Bootstrap 5 classes, data-bs-toggle, float-end.
     *               Tailwind (TailwindAdapter): Tailwind utility classes.
     */
    public function renderPageActionButtons(object $route_info): string;

    /**
     * Return the dismiss attribute name appropriate for the active CSS framework.
     *
     * @return string The attribute name string.
     *               Bootstrap 4 (DefaultAdapter): `'data-dismiss'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'data-bs-dismiss'`
     */
    public function getDismissAttribute(): string;

    /**
     * Return the CSS class used to hide elements for the active CSS framework.
     *
     * @return string CSS class string for hiding elements.
     *               Bootstrap 4 (DefaultAdapter): `'hide'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'d-none'`
     *               Tailwind (TailwindAdapter): `'hidden'`
     */
    public function getHideClass(): string;

    /**
     * Return the CSS class used for float-right / end alignment for the active CSS framework.
     *
     * @return string CSS class string for right-side alignment.
     *               Bootstrap 4 (DefaultAdapter): `'pull-right'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'float-end'`
     *               Tailwind (TailwindAdapter): `'ml-auto'`
     */
    public function getFloatRightClass(): string;

    /**
     * Render the outer wrapper div for a regular checkbox element.
     *
     * @param string $checkboxType CSS class modifier for the checkbox type (e.g. ' ckbox-primary').
     *                             For Bootstrap 5 / Tailwind this parameter is ignored or used differently.
     * @param string $inputHtml    The rendered <input type="checkbox"> HTML string.
     * @param string $labelHtml    The rendered <label> HTML string.
     *
     * @return string Rendered HTML string for the checkbox wrapper element.
     *               Bootstrap 4 (DefaultAdapter): `<div class="col-sm-3 ckbox{$checkboxType}">{$inputHtml}{$labelHtml}</div>`
     *               Bootstrap 5 (Bootstrap5Adapter): `<div class="form-check">{$inputHtml}{$labelHtml}</div>`
     *               Tailwind (TailwindAdapter): `<div class="flex items-center gap-2">{$inputHtml}{$labelHtml}</div>`
     */
    public function renderCheckboxWrapper(string $checkboxType, string $inputHtml, string $labelHtml): string;

    /**
     * Render the outer tab container wrapping all tab headers and content panes.
     *
     * @param string $headersHtml  Concatenated HTML of all rendered tab header items.
     * @param string $contentsHtml Concatenated HTML of all rendered tab content panes.
     *
     * @return string Rendered HTML string for the complete tab container.
     *               Bootstrap 4 (DefaultAdapter):
     *                 `<div class="tabbable"><ul class="nav nav-tabs" role="tablist">{$headersHtml}</ul><div class="tab-content">{$contentsHtml}</div></div><br />`
     *               Bootstrap 5 (Bootstrap5Adapter): same structure (Bootstrap 5 nav-tabs is compatible).
     *               Tailwind (TailwindAdapter):
     *                 `<div class="w-full"><div class="flex border-b border-gray-200" role="tablist">{$headersHtml}</div><div class="tab-content pt-4">{$contentsHtml}</div></div>`
     */
    public function renderTabWrapper(string $headersHtml, string $contentsHtml): string;

    // ── Grid System Methods ───────────────────────────────────────────────

    /**
     * Return the CSS class for grid container element.
     *
     * @return string CSS class string for container.
     *               Bootstrap 4 (DefaultAdapter): `'container'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'container'`
     *               Tailwind (TailwindAdapter): `'container mx-auto'`
     */
    public function getContainerClass(): string;

    /**
     * Return the CSS class for grid row element.
     *
     * @return string CSS class string for row.
     *               Bootstrap 4 (DefaultAdapter): `'row'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'row'`
     *               Tailwind (TailwindAdapter): `'flex flex-wrap'`
     */
    public function getRowClass(): string;

    /**
     * Return the CSS class for grid column element based on column count.
     *
     * @param int $columns Number of columns (1-12 in Bootstrap grid system).
     *
     * @return string CSS class string for column.
     *               Bootstrap 4 (DefaultAdapter): `"col-{$columns}"`
     *               Bootstrap 5 (Bootstrap5Adapter): `"col-{$columns}"`
     *               Tailwind (TailwindAdapter): Tailwind width classes based on 12-column grid
     */
    public function getColumnClass(int $columns): string;

    // ── Template Helper Methods ───────────────────────────────────────────

    /**
     * Render HTML breadcrumb navigation.
     *
     * @param string       $title      The page title displayed in the breadcrumb area.
     * @param array        $links      Associative array of breadcrumb links: ['Link Title' => 'url'].
     *                                 Use numeric key (0) for non-linked items: [0 => 'Current Page'].
     * @param string|false $iconTitle  Icon class for the title (e.g. 'fa-home'), or false to omit.
     * @param array|false  $iconLinks  Array of icon classes for links, or false to omit.
     * @param string|false $type       Breadcrumb type ('blankon' for legacy style, false for default).
     *
     * @return string Rendered HTML string for the breadcrumb element.
     *               Bootstrap 4 (DefaultAdapter): uses `breadcrumb-wrapper`, `breadcrumb`, `breadcrumbs-area`, `pull-right`.
     *               Bootstrap 5 (Bootstrap5Adapter): uses Bootstrap 5 breadcrumb classes, replaces `pull-right` with `float-end`.
     *               Tailwind (TailwindAdapter): uses `flex`, `items-center`, `space-x-2`, `text-sm` utility classes.
     */
    public function renderBreadcrumb(
        string $title,
        array $links,
        string|false $iconTitle,
        array|false $iconLinks,
        string|false $type
    ): string;

    // ── Sidebar Methods ───────────────────────────────────────────────────

    /**
     * Render HTML sidebar content.
     *
     * @param string       $mediaTitle      The main content/title HTML for the sidebar.
     * @param string|false $mediaHeading    The heading text, or false to omit.
     * @param string|false $mediaSubHeading The sub-heading text, or false to omit.
     * @param bool         $type            Sidebar type: true for user panel, false for simple sidebar.
     *
     * @return string Rendered HTML string for the sidebar content element.
     *               Bootstrap 4 (DefaultAdapter): uses `sidebar-content`, `media`, `media-body`, `pull-left`.
     *               Bootstrap 5 (Bootstrap5Adapter): uses Bootstrap 5 classes, replaces `pull-left` with `float-start`.
     *               Tailwind (TailwindAdapter): uses `flex`, `items-center`, `gap-3` utility classes.
     */
    public function renderSidebarContent(
        string $mediaTitle,
        string|false $mediaHeading,
        string|false $mediaSubHeading,
        bool $type
    ): string;

    /**
     * Return the CSS class used for float-left / start alignment for the active CSS framework.
     *
     * @return string CSS class string for left-side alignment.
     *               Bootstrap 4 (DefaultAdapter): `'pull-left'`
     *               Bootstrap 5 (Bootstrap5Adapter): `'float-start'`
     *               Tailwind (TailwindAdapter): `'float-left'` or `'mr-auto'`
     */
    public function getFloatLeftClass(): string;
}
