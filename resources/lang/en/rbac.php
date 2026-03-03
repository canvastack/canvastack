<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | RBAC Language Lines (English)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the RBAC system for
    | fine-grained permissions, including row-level, column-level,
    | JSON attribute-level, and conditional permissions.
    |
    */

    // Fine-Grained Permissions
    'fine_grained' => [
        // General
        'title' => 'Fine-Grained Permissions',
        'description' => 'Manage granular access control at row, column, and attribute levels',

        // Permission Types
        'row_level' => 'Row-Level Permission',
        'column_level' => 'Column-Level Permission',
        'json_attribute' => 'JSON Attribute Permission',
        'conditional' => 'Conditional Permission',

        // Rule Management
        'manage_rules' => 'Manage Permission Rules',
        'add_rule' => 'Add Permission Rule',
        'edit_rule' => 'Edit Permission Rule',
        'delete_rule' => 'Delete Permission Rule',
        'rule_type' => 'Rule Type',
        'rule_config' => 'Rule Configuration',
        'priority' => 'Priority',

        // Row-Level Permissions
        'row_level_description' => 'Control access to specific data rows based on conditions',
        'conditions' => 'Conditions',
        'add_condition' => 'Add Condition',
        'field' => 'Field',
        'operator' => 'Operator',
        'value' => 'Value',
        'template_variables' => 'Template Variables',
        'template_variables_help' => 'Use {{auth.id}}, {{auth.role}}, {{auth.department}}, etc.',

        // Column-Level Permissions
        'column_level_description' => 'Control access to specific fields in a model',
        'allowed_columns' => 'Allowed Columns',
        'denied_columns' => 'Denied Columns',
        'mode' => 'Mode',
        'whitelist' => 'Whitelist (Allow Only)',
        'blacklist' => 'Blacklist (Deny Only)',
        'select_columns' => 'Select Columns',

        // JSON Attribute Permissions
        'json_attribute_description' => 'Control access to nested fields within JSON columns',
        'json_column' => 'JSON Column',
        'allowed_paths' => 'Allowed Paths',
        'denied_paths' => 'Denied Paths',
        'path_separator' => 'Path Separator',
        'path_help' => 'Use dot notation (e.g., metadata.seo.title) or wildcards (e.g., seo.*)',

        // Conditional Permissions
        'conditional_description' => 'Grant access based on dynamic conditions',
        'condition' => 'Condition',
        'condition_expression' => 'Condition Expression',
        'allowed_operators' => 'Allowed Operators',
        'condition_help' => 'Example: status === "draft" AND user_id === {{auth.id}}',

        // User Overrides
        'user_overrides' => 'User Permission Overrides',
        'add_override' => 'Add User Override',
        'edit_override' => 'Edit User Override',
        'delete_override' => 'Delete User Override',
        'override_type' => 'Override Type',
        'model_type' => 'Model Type',
        'model_id' => 'Model ID',
        'field_name' => 'Field Name',
        'allowed' => 'Allowed',
        'denied' => 'Denied',

        // Messages
        'rule_created' => 'Permission rule created successfully',
        'rule_updated' => 'Permission rule updated successfully',
        'rule_deleted' => 'Permission rule deleted successfully',
        'override_created' => 'User override created successfully',
        'override_updated' => 'User override updated successfully',
        'override_deleted' => 'User override deleted successfully',

        // Errors
        'rule_not_found' => 'Permission rule not found',
        'override_not_found' => 'User override not found',
        'invalid_rule_type' => 'Invalid rule type',
        'invalid_condition' => 'Invalid condition expression',
        'invalid_operator' => 'Invalid operator',
        'invalid_column' => 'Invalid column name',
        'invalid_json_path' => 'Invalid JSON path',

        // Access Denied Messages
        'access_denied' => 'Access denied',
        'row_access_denied' => 'You do not have permission to access this row',
        'column_access_denied' => 'You do not have permission to access this field',
        'json_attribute_access_denied' => 'You do not have permission to access this attribute',
        'no_access' => 'No Access',

        // UI Indicators
        'field_hidden' => 'The :field field is hidden due to permissions',
        'field_readonly' => 'This field is read-only',
        'columns_hidden' => '{1} :count column is hidden due to permissions|[2,*] :count columns are hidden due to permissions',
        'some_fields_hidden' => 'Some fields are hidden based on your permissions',
        'json_field_hidden' => 'The nested field :field is hidden due to permissions',
        'json_fields_hidden' => '{1} :count nested field is hidden due to permissions|[2,*] :count nested fields are hidden due to permissions',

        // Cache
        'cache_cleared' => 'Permission cache cleared successfully',
        'cache_warmed' => 'Permission cache warmed up successfully',

        // Validation
        'validation' => [
            'rule_type_required' => 'Rule type is required',
            'rule_config_required' => 'Rule configuration is required',
            'permission_id_required' => 'Permission ID is required',
            'user_id_required' => 'User ID is required',
            'model_type_required' => 'Model type is required',
            'conditions_required' => 'At least one condition is required',
            'columns_required' => 'At least one column must be specified',
            'paths_required' => 'At least one path must be specified',
            'condition_expression_required' => 'Condition expression is required',
        ],
    ],

    // Basic RBAC (existing)
    'roles' => [
        'title' => 'Roles',
        'create' => 'Create Role',
        'edit' => 'Edit Role',
        'delete' => 'Delete Role',
        'name' => 'Role Name',
        'description' => 'Description',
        'permissions' => 'Permissions',
    ],

    'permissions' => [
        'title' => 'Permissions',
        'create' => 'Create Permission',
        'edit' => 'Edit Permission',
        'delete' => 'Delete Permission',
        'name' => 'Permission Name',
        'display_name' => 'Display Name',
        'description' => 'Description',
        'module' => 'Module',
    ],

    'groups' => [
        'title' => 'Groups',
        'create' => 'Create Group',
        'edit' => 'Edit Group',
        'delete' => 'Delete Group',
        'name' => 'Group Name',
        'description' => 'Description',
        'roles' => 'Roles',
    ],

    // Messages
    'messages' => [
        'role_created' => 'Role created successfully',
        'role_updated' => 'Role updated successfully',
        'role_deleted' => 'Role deleted successfully',
        'permission_created' => 'Permission created successfully',
        'permission_updated' => 'Permission updated successfully',
        'permission_deleted' => 'Permission deleted successfully',
        'group_created' => 'Group created successfully',
        'group_updated' => 'Group updated successfully',
        'group_deleted' => 'Group deleted successfully',
        'access_denied' => 'You do not have permission to perform this action',
    ],

    // Errors
    'errors' => [
        'role_not_found' => 'Role not found',
        'permission_not_found' => 'Permission not found',
        'group_not_found' => 'Group not found',
        'invalid_permission' => 'Invalid permission',
        'cannot_delete_role' => 'Cannot delete role: it is assigned to users',
        'cannot_delete_permission' => 'Cannot delete permission: it is assigned to roles',
    ],
];
