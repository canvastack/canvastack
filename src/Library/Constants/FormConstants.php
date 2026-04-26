<?php

namespace Canvastack\Canvastack\Library\Constants;

/**
 * FormConstants Class
 * 
 * Centralized constants for Form Components to replace magic strings.
 * This class provides type-safe constants for CSS classes, HTML attributes,
 * ARIA attributes, file paths, and other form-related values.
 * 
 * @package    Canvastack\Canvastack\Library\Constants
 * @author     wisnuwidi@canvastack.com
 * @copyright  2026 Canvastack
 * @version    1.0.0
 * @since      31 Mar 2026
 * @version    1.0.0
 */
class FormConstants
{
    // ========================================================================
    // CSS Classes
    // ========================================================================
    
    /**
     * Bootstrap form control class for input styling
     */
    public const CLASS_FORM_CONTROL = 'form-control';
    
    /**
     * Bootstrap button class
     */
    public const CLASS_BTN = 'btn';
    
    /**
     * Chosen select plugin classes for enhanced dropdowns
     */
    public const CLASS_CHOSEN_SELECT = 'chosen-select-deselect chosen-selectbox';
    
    /**
     * Default selectbox class (alias for CLASS_CHOSEN_SELECT)
     */
    public const DEFAULT_SELECTBOX_CLASS = 'chosen-select-deselect chosen-selectbox';
    
    /**
     * Checkbox wrapper class
     */
    public const CLASS_CKBOX = 'ckbox';
    
    /**
     * Primary styled checkbox class
     */
    public const CLASS_CKBOX_PRIMARY = 'ckbox-primary';
    
    /**
     * Switch toggle class for checkbox styling
     */
    public const CLASS_SWITCH = 'switch';
    
    /**
     * CKEditor rich text editor class
     */
    public const CLASS_CKEDITOR = 'ckeditor';
    
    /**
     * Tags input plugin class for tag management
     */
    public const CLASS_TAGSINPUT = 'tagsinput';
    
    /**
     * Datepicker plugin class for date selection
     */
    public const CLASS_DATEPICKER = 'datepicker';
    
    /**
     * Timepicker plugin class for time selection
     */
    public const CLASS_TIMEPICKER = 'timepicker';
    
    // ========================================================================
    // HTML Attributes
    // ========================================================================
    
    /**
     * HTML class attribute
     */
    public const ATTR_CLASS = 'class';
    
    /**
     * HTML id attribute
     */
    public const ATTR_ID = 'id';
    
    /**
     * HTML role attribute for semantic meaning
     */
    public const ATTR_ROLE = 'role';
    
    /**
     * Data-role attribute for custom role definitions
     */
    public const ATTR_DATA_ROLE = 'data-role';
    
    /**
     * Placeholder attribute for input hints
     */
    public const ATTR_PLACEHOLDER = 'placeholder';
    
    /**
     * Maxlength attribute for input length restriction
     */
    public const ATTR_MAXLENGTH = 'maxlength';
    
    /**
     * Disabled attribute for non-interactive elements
     */
    public const ATTR_DISABLED = 'disabled';
    
    /**
     * Readonly attribute for non-editable inputs
     */
    public const ATTR_READONLY = 'readonly';
    
    /**
     * Required attribute for mandatory fields
     */
    public const ATTR_REQUIRED = 'required';
    
    // ========================================================================
    // ARIA Attributes
    // ========================================================================
    
    /**
     * ARIA label for accessible element naming
     */
    public const ARIA_LABEL = 'aria-label';
    
    /**
     * ARIA checked state for checkboxes and radio buttons
     */
    public const ARIA_CHECKED = 'aria-checked';
    
    /**
     * ARIA disabled state for non-interactive elements
     */
    public const ARIA_DISABLED = 'aria-disabled';
    
    /**
     * ARIA required state for mandatory fields
     */
    public const ARIA_REQUIRED = 'aria-required';
    
    /**
     * ARIA invalid state for validation errors
     */
    public const ARIA_INVALID = 'aria-invalid';
    
    /**
     * ARIA describedby for associating descriptions
     */
    public const ARIA_DESCRIBEDBY = 'aria-describedby';
    
    /**
     * ARIA live region for dynamic content announcements
     */
    public const ARIA_LIVE = 'aria-live';
    
    /**
     * ARIA selected state for selected items (tabs, options)
     */
    public const ARIA_SELECTED = 'aria-selected';
    
    /**
     * ARIA controls for associating controlling elements
     */
    public const ARIA_CONTROLS = 'aria-controls';
    
    /**
     * ARIA labelledby for associating labels
     */
    public const ARIA_LABELLEDBY = 'aria-labelledby';
    
    /**
     * ARIA hidden for hiding elements from screen readers
     */
    public const ARIA_HIDDEN = 'aria-hidden';
    
    // ========================================================================
    // File Paths
    // ========================================================================
    
    /**
     * Thumbnail directory path for image thumbnails
     */
    public const PATH_THUMB = 'thumb';
    
    /**
     * Assets directory path for uploaded files
     */
    public const PATH_ASSETS = 'assets';
    
    // ========================================================================
    // Tab Markers
    // ========================================================================
    
    /**
     * Opening marker for tab HTML form sections
     */
    public const MARKER_OPEN_TAB = '--[openTabHTMLForm]--';
    
    /**
     * Closing marker for tab HTML form sections
     */
    public const MARKER_CLOSE_TAB = '--[closeTabHTMLForm]--';
    
    // ========================================================================
    // Plugin Names
    // ========================================================================
    
    /**
     * CKEditor plugin identifier
     */
    public const PLUGIN_CKEDITOR = 'ckeditor';
    
    /**
     * Tags input plugin identifier
     */
    public const PLUGIN_TAGSINPUT = 'tagsinput';
    
    /**
     * Datepicker plugin identifier
     */
    public const PLUGIN_DATEPICKER = 'datepicker';
    
    /**
     * Timepicker plugin identifier
     */
    public const PLUGIN_TIMEPICKER = 'timepicker';
    
    /**
     * Chosen select plugin identifier
     */
    public const PLUGIN_CHOSEN = 'chosen';
    
    // ========================================================================
    // Validation Rules
    // ========================================================================
    
    /**
     * Required field validation rule
     */
    public const VALIDATION_REQUIRED = 'required';
    
    /**
     * Email format validation rule
     */
    public const VALIDATION_EMAIL = 'email';
    
    /**
     * Numeric value validation rule
     */
    public const VALIDATION_NUMERIC = 'numeric';
    
    /**
     * Minimum value validation rule
     */
    public const VALIDATION_MIN = 'min';
    
    /**
     * Maximum value validation rule
     */
    public const VALIDATION_MAX = 'max';
    
    /**
     * MIME type validation rule for file uploads
     */
    public const VALIDATION_MIMES = 'mimes';
    
    /**
     * Maximum file size validation rule
     */
    public const VALIDATION_MAX_FILE_SIZE = 'max';
    
    // ========================================================================
    // Check Types
    // ========================================================================
    
    /**
     * Primary checkbox/switch style
     */
    public const CHECK_TYPE_PRIMARY = 'primary';
    
    /**
     * Success checkbox/switch style (green)
     */
    public const CHECK_TYPE_SUCCESS = 'success';
    
    /**
     * Danger checkbox/switch style (red)
     */
    public const CHECK_TYPE_DANGER = 'danger';
    
    /**
     * Warning checkbox/switch style (yellow)
     */
    public const CHECK_TYPE_WARNING = 'warning';
    
    /**
     * Info checkbox/switch style (blue)
     */
    public const CHECK_TYPE_INFO = 'info';
    
    /**
     * Switch toggle type
     */
    public const CHECK_TYPE_SWITCH = 'switch';
    
    // ========================================================================
    // Alert Types
    // ========================================================================
    
    /**
     * Success alert type (green)
     */
    public const ALERT_SUCCESS = 'success';
    
    /**
     * Danger alert type (red)
     */
    public const ALERT_DANGER = 'danger';
    
    /**
     * Warning alert type (yellow)
     */
    public const ALERT_WARNING = 'warning';
    
    /**
     * Info alert type (blue)
     */
    public const ALERT_INFO = 'info';
    
    // ========================================================================
    // ARIA Live Values
    // ========================================================================
    
    /**
     * ARIA live assertive - interrupts screen reader immediately
     * Use for critical errors and urgent messages
     */
    public const ARIA_LIVE_ASSERTIVE = 'assertive';
    
    /**
     * ARIA live polite - waits for screen reader to finish
     * Use for success messages and non-critical updates
     */
    public const ARIA_LIVE_POLITE = 'polite';
    
    /**
     * ARIA live off - disables live region announcements
     */
    public const ARIA_LIVE_OFF = 'off';
    
    // ========================================================================
    // Delimiters
    // ========================================================================
    
    /**
     * Icon delimiter for parsing icon configuration strings
     * Used in format: "fieldname|iconname|position"
     */
    public const ICON_DELIMITER = '|';
    
    // ========================================================================
    // Status Values
    // ========================================================================
    
    /**
     * Active status value for "Yes" or "Active" state
     */
    public const ACTIVE_STATUS_YES = 1;
    
    /**
     * Active status value for "No" or "Inactive" state
     */
    public const ACTIVE_STATUS_NO = 0;
    
    /**
     * Request status: Pending
     */
    public const REQUEST_STATUS_PENDING = 0;
    
    /**
     * Request status: Accept
     */
    public const REQUEST_STATUS_ACCEPT = 1;
    
    /**
     * Request status: Blocked
     */
    public const REQUEST_STATUS_BLOCKED = 2;
    
    /**
     * Request status: Ban
     */
    public const REQUEST_STATUS_BAN = 3;
}
