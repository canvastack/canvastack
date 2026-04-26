<?php

namespace Canvastack\Canvastack\Library\Constants;

/**
 * TableConstants Class
 *
 * Centralized constants for Table Components to replace magic strings.
 * This class provides type-safe constants for CSS classes, HTML attributes,
 * ARIA attributes, DataTables options, action buttons, column types,
 * filter operators, sort directions, export formats, special columns,
 * default values, ARIA roles, and cache keys.
 *
 * @package    Canvastack\Canvastack\Library\Constants
 * @author     wisnuwidi@canvastack.com
 * @copyright  2026 Canvastack
 * @version    1.0.0
 * @since      2026
 */
class TableConstants
{
    // ========================================================================
    // CSS Classes
    // ========================================================================

    /**
     * Bootstrap base table class
     */
    public const CLASS_TABLE = 'table';

    /**
     * Bootstrap striped table class
     */
    public const CLASS_TABLE_STRIPED = 'table-striped';

    /**
     * Bootstrap bordered table class
     */
    public const CLASS_TABLE_BORDERED = 'table-bordered';

    /**
     * Bootstrap hover table class
     */
    public const CLASS_TABLE_HOVER = 'table-hover';

    /**
     * DataTables base class applied to initialized tables
     */
    public const CLASS_DATATABLE = 'dataTable';

    /**
     * DataTables responsive extension class
     */
    public const CLASS_RESPONSIVE = 'responsive';

    /**
     * DataTables nowrap class to prevent text wrapping
     */
    public const CLASS_NOWRAP = 'nowrap';

    /**
     * Canvastack-specific table identifier class
     */
    public const CLASS_CANVASTACK_TABLE = 'CanvaStack-table';

    // ========================================================================
    // HTML Attributes
    // ========================================================================

    /**
     * HTML id attribute name
     */
    public const ATTR_ID = 'id';

    /**
     * HTML class attribute name
     */
    public const ATTR_CLASS = 'class';

    /**
     * HTML width attribute name
     */
    public const ATTR_WIDTH = 'width';

    /**
     * HTML role attribute name
     */
    public const ATTR_ROLE = 'role';

    /**
     * ARIA label attribute name
     */
    public const ATTR_ARIA_LABEL = 'aria-label';

    /**
     * ARIA sort attribute name for sortable column headers
     */
    public const ATTR_ARIA_SORT = 'aria-sort';

    /**
     * ARIA busy attribute name for loading state indication
     */
    public const ATTR_ARIA_BUSY = 'aria-busy';

    // ========================================================================
    // DataTables Options
    // ========================================================================

    /**
     * DataTables processing indicator option key
     */
    public const DT_PROCESSING = 'processing';

    /**
     * DataTables server-side processing option key
     */
    public const DT_SERVER_SIDE = 'serverSide';

    /**
     * DataTables AJAX source option key
     */
    public const DT_AJAX = 'ajax';

    /**
     * DataTables columns definition option key
     */
    public const DT_COLUMNS = 'columns';

    /**
     * DataTables initial order option key
     */
    public const DT_ORDER = 'order';

    /**
     * DataTables page length option key
     */
    public const DT_PAGE_LENGTH = 'pageLength';

    /**
     * DataTables searching feature option key
     */
    public const DT_SEARCHING = 'searching';

    /**
     * DataTables ordering feature option key
     */
    public const DT_ORDERING = 'ordering';

    // ========================================================================
    // Action Button Names
    // ========================================================================

    /**
     * View/detail action button name
     */
    public const ACTION_VIEW = 'view';

    /**
     * Edit action button name
     */
    public const ACTION_EDIT = 'edit';

    /**
     * Delete action button name
     */
    public const ACTION_DELETE = 'delete';

    /**
     * Insert/create action button name
     */
    public const ACTION_INSERT = 'insert';

    /**
     * Restore soft-deleted record action button name
     */
    public const ACTION_RESTORE = 'restore_deleted';

    // ========================================================================
    // Column Types
    // ========================================================================

    /**
     * String/text column type
     */
    public const TYPE_STRING = 'string';

    /**
     * Integer column type
     */
    public const TYPE_INTEGER = 'integer';

    /**
     * Decimal/float column type
     */
    public const TYPE_DECIMAL = 'decimal';

    /**
     * Date column type (date only, no time)
     */
    public const TYPE_DATE = 'date';

    /**
     * Datetime column type (date and time)
     */
    public const TYPE_DATETIME = 'datetime';

    /**
     * Boolean column type
     */
    public const TYPE_BOOLEAN = 'boolean';

    // ========================================================================
    // Filter Operators
    // ========================================================================

    /**
     * Equality filter operator
     */
    public const OP_EQUALS = '=';

    /**
     * Inequality filter operator
     */
    public const OP_NOT_EQUALS = '!=';

    /**
     * Greater-than filter operator
     */
    public const OP_GREATER_THAN = '>';

    /**
     * Less-than filter operator
     */
    public const OP_LESS_THAN = '<';

    /**
     * Greater-than-or-equal filter operator
     */
    public const OP_GREATER_EQUAL = '>=';

    /**
     * Less-than-or-equal filter operator
     */
    public const OP_LESS_EQUAL = '<=';

    /**
     * SQL LIKE pattern-matching filter operator
     */
    public const OP_LIKE = 'LIKE';

    /**
     * SQL IN set-membership filter operator
     */
    public const OP_IN = 'IN';

    /**
     * SQL NOT IN set-exclusion filter operator
     */
    public const OP_NOT_IN = 'NOT IN';

    /**
     * SQL BETWEEN range filter operator
     */
    public const OP_BETWEEN = 'BETWEEN';

    /**
     * SQL IS NULL null-check filter operator
     */
    public const OP_IS_NULL = 'IS NULL';

    /**
     * SQL IS NOT NULL non-null-check filter operator
     */
    public const OP_IS_NOT_NULL = 'IS NOT NULL';

    // ========================================================================
    // Sort Directions
    // ========================================================================

    /**
     * Ascending sort direction
     */
    public const SORT_ASC = 'asc';

    /**
     * Descending sort direction
     */
    public const SORT_DESC = 'desc';

    // ========================================================================
    // Export Formats
    // ========================================================================

    /**
     * CSV export format identifier
     */
    public const EXPORT_CSV = 'csv';

    /**
     * Excel export format identifier
     */
    public const EXPORT_EXCEL = 'excel';

    /**
     * PDF export format identifier
     */
    public const EXPORT_PDF = 'pdf';

    // ========================================================================
    // Special Column Names
    // ========================================================================

    /**
     * Special column name for row numbering
     */
    public const COL_NUMBER_LISTS = 'number_lists';

    /**
     * Special column name for action buttons
     */
    public const COL_ACTION = 'action';

    /**
     * Special column name for row number display
     */
    public const COL_NO = 'no';

    /**
     * Special column name for primary key identifier
     */
    public const COL_ID = 'id';

    // ========================================================================
    // Default Values
    // ========================================================================

    /**
     * Default number of rows per page
     */
    public const DEFAULT_PAGE_LENGTH = 10;

    /**
     * Default pagination start offset
     */
    public const DEFAULT_START = 0;

    /**
     * Maximum allowed page length to prevent memory exhaustion
     */
    public const MAX_PAGE_LENGTH = 100;

    /**
     * Default database connection name
     */
    public const DEFAULT_DB_CONNECTION = 'mysql';

    // ========================================================================
    // ARIA Roles
    // ========================================================================

    /**
     * ARIA role value for table element
     */
    public const ROLE_TABLE = 'table';

    /**
     * ARIA role value for table row element
     */
    public const ROLE_ROW = 'row';

    /**
     * ARIA role value for table data cell element
     */
    public const ROLE_CELL = 'cell';

    /**
     * ARIA role value for column header cell element
     */
    public const ROLE_COLUMNHEADER = 'columnheader';

    /**
     * ARIA role value for row header cell element
     */
    public const ROLE_ROWHEADER = 'rowheader';

    // ========================================================================
    // Cache Keys
    // ========================================================================

    /**
     * Cache key prefix for table schema data (column names + types)
     */
    public const CACHE_SCHEMA_PREFIX = 'table_schema_';

    /**
     * Cache key prefix for table column-list data (names only)
     */
    public const CACHE_COLUMNS_PREFIX = 'table_columns_';

    /**
     * Cache key prefix for table configuration data
     */
    public const CACHE_CONFIG_PREFIX = 'table_config_';

    /**
     * Cache key prefix for validation result data
     */
    public const CACHE_VALIDATION_PREFIX = 'table_validation_';

    // ========================================================================
    // Cache TTL Values
    // ========================================================================

    /**
     * Default cache TTL in seconds (1 hour)
     * Used as a general-purpose fallback when no specific TTL is configured.
     */
    public const CACHE_TTL = 3600;

    /**
     * Schema cache TTL in seconds (6 hours)
     * Table schemas rarely change; a longer TTL reduces DB round-trips significantly.
     * Invalidate explicitly after running migrations via canvastack_table_invalidate_schema_cache().
     */
    public const CACHE_SCHEMA_TTL = 21600;

    /**
     * Configuration cache TTL in seconds (30 minutes)
     * Table configs (column defs, actions) may change more often than schema.
     */
    public const CACHE_CONFIG_TTL = 1800;

    /**
     * Validation result cache TTL in seconds (10 minutes)
     * Short TTL for validation results (e.g. image existence) that can change frequently.
     */
    public const CACHE_VALIDATION_TTL = 600;
}
