<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | RBAC Language Lines (Indonesian)
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut digunakan oleh sistem RBAC untuk
    | izin granular, termasuk izin tingkat baris, kolom,
    | atribut JSON, dan izin kondisional.
    |
    */

    // Fine-Grained Permissions
    'fine_grained' => [
        // General
        'title' => 'Izin Granular',
        'description' => 'Kelola kontrol akses granular di tingkat baris, kolom, dan atribut',

        // Permission Types
        'row_level' => 'Izin Tingkat Baris',
        'column_level' => 'Izin Tingkat Kolom',
        'json_attribute' => 'Izin Atribut JSON',
        'conditional' => 'Izin Kondisional',

        // Rule Management
        'manage_rules' => 'Kelola Aturan Izin',
        'add_rule' => 'Tambah Aturan Izin',
        'edit_rule' => 'Edit Aturan Izin',
        'delete_rule' => 'Hapus Aturan Izin',
        'rule_type' => 'Tipe Aturan',
        'rule_config' => 'Konfigurasi Aturan',
        'priority' => 'Prioritas',

        // Row-Level Permissions
        'row_level_description' => 'Kontrol akses ke baris data tertentu berdasarkan kondisi',
        'conditions' => 'Kondisi',
        'add_condition' => 'Tambah Kondisi',
        'field' => 'Field',
        'operator' => 'Operator',
        'value' => 'Nilai',
        'template_variables' => 'Variabel Template',
        'template_variables_help' => 'Gunakan {{auth.id}}, {{auth.role}}, {{auth.department}}, dll.',

        // Column-Level Permissions
        'column_level_description' => 'Kontrol akses ke field tertentu dalam model',
        'allowed_columns' => 'Kolom yang Diizinkan',
        'denied_columns' => 'Kolom yang Ditolak',
        'mode' => 'Mode',
        'whitelist' => 'Whitelist (Hanya Izinkan)',
        'blacklist' => 'Blacklist (Hanya Tolak)',
        'select_columns' => 'Pilih Kolom',

        // JSON Attribute Permissions
        'json_attribute_description' => 'Kontrol akses ke field bersarang dalam kolom JSON',
        'json_column' => 'Kolom JSON',
        'allowed_paths' => 'Path yang Diizinkan',
        'denied_paths' => 'Path yang Ditolak',
        'path_separator' => 'Pemisah Path',
        'path_help' => 'Gunakan notasi titik (contoh: metadata.seo.title) atau wildcard (contoh: seo.*)',

        // Conditional Permissions
        'conditional_description' => 'Berikan akses berdasarkan kondisi dinamis',
        'condition' => 'Kondisi',
        'condition_expression' => 'Ekspresi Kondisi',
        'allowed_operators' => 'Operator yang Diizinkan',
        'condition_help' => 'Contoh: status === "draft" AND user_id === {{auth.id}}',

        // User Overrides
        'user_overrides' => 'Override Izin Pengguna',
        'add_override' => 'Tambah Override Pengguna',
        'edit_override' => 'Edit Override Pengguna',
        'delete_override' => 'Hapus Override Pengguna',
        'override_type' => 'Tipe Override',
        'model_type' => 'Tipe Model',
        'model_id' => 'ID Model',
        'field_name' => 'Nama Field',
        'allowed' => 'Diizinkan',
        'denied' => 'Ditolak',

        // Messages
        'rule_created' => 'Aturan izin berhasil dibuat',
        'rule_updated' => 'Aturan izin berhasil diperbarui',
        'rule_deleted' => 'Aturan izin berhasil dihapus',
        'override_created' => 'Override pengguna berhasil dibuat',
        'override_updated' => 'Override pengguna berhasil diperbarui',
        'override_deleted' => 'Override pengguna berhasil dihapus',

        // Errors
        'rule_not_found' => 'Aturan izin tidak ditemukan',
        'override_not_found' => 'Override pengguna tidak ditemukan',
        'invalid_rule_type' => 'Tipe aturan tidak valid',
        'invalid_condition' => 'Ekspresi kondisi tidak valid',
        'invalid_operator' => 'Operator tidak valid',
        'invalid_column' => 'Nama kolom tidak valid',
        'invalid_json_path' => 'Path JSON tidak valid',

        // Access Denied Messages
        'access_denied' => 'Akses ditolak',
        'row_access_denied' => 'Anda tidak memiliki izin untuk mengakses baris ini',
        'column_access_denied' => 'Anda tidak memiliki izin untuk mengakses field ini',
        'json_attribute_access_denied' => 'Anda tidak memiliki izin untuk mengakses atribut ini',
        'no_access' => 'Tidak Ada Akses',

        // UI Indicators
        'field_hidden' => 'Field :field disembunyikan karena izin',
        'field_readonly' => 'Field ini hanya bisa dibaca',
        'columns_hidden' => '{1} :count kolom disembunyikan karena izin|[2,*] :count kolom disembunyikan karena izin',
        'some_fields_hidden' => 'Beberapa field disembunyikan berdasarkan izin Anda',
        'json_field_hidden' => 'Field bersarang :field disembunyikan karena izin',
        'json_fields_hidden' => '{1} :count field bersarang disembunyikan karena izin|[2,*] :count field bersarang disembunyikan karena izin',

        // Cache
        'cache_cleared' => 'Cache izin berhasil dibersihkan',
        'cache_warmed' => 'Cache izin berhasil dipanaskan',

        // Validation
        'validation' => [
            'rule_type_required' => 'Tipe aturan wajib diisi',
            'rule_config_required' => 'Konfigurasi aturan wajib diisi',
            'permission_id_required' => 'ID izin wajib diisi',
            'user_id_required' => 'ID pengguna wajib diisi',
            'model_type_required' => 'Tipe model wajib diisi',
            'conditions_required' => 'Minimal satu kondisi harus ditentukan',
            'columns_required' => 'Minimal satu kolom harus ditentukan',
            'paths_required' => 'Minimal satu path harus ditentukan',
            'condition_expression_required' => 'Ekspresi kondisi wajib diisi',
        ],
    ],

    // Basic RBAC (existing)
    'roles' => [
        'title' => 'Peran',
        'create' => 'Buat Peran',
        'edit' => 'Edit Peran',
        'delete' => 'Hapus Peran',
        'name' => 'Nama Peran',
        'description' => 'Deskripsi',
        'permissions' => 'Izin',
    ],

    'permissions' => [
        'title' => 'Izin',
        'create' => 'Buat Izin',
        'edit' => 'Edit Izin',
        'delete' => 'Hapus Izin',
        'name' => 'Nama Izin',
        'display_name' => 'Nama Tampilan',
        'description' => 'Deskripsi',
        'module' => 'Modul',
    ],

    'groups' => [
        'title' => 'Grup',
        'create' => 'Buat Grup',
        'edit' => 'Edit Grup',
        'delete' => 'Hapus Grup',
        'name' => 'Nama Grup',
        'description' => 'Deskripsi',
        'roles' => 'Peran',
    ],

    // Messages
    'messages' => [
        'role_created' => 'Peran berhasil dibuat',
        'role_updated' => 'Peran berhasil diperbarui',
        'role_deleted' => 'Peran berhasil dihapus',
        'permission_created' => 'Izin berhasil dibuat',
        'permission_updated' => 'Izin berhasil diperbarui',
        'permission_deleted' => 'Izin berhasil dihapus',
        'group_created' => 'Grup berhasil dibuat',
        'group_updated' => 'Grup berhasil diperbarui',
        'group_deleted' => 'Grup berhasil dihapus',
        'access_denied' => 'Anda tidak memiliki izin untuk melakukan tindakan ini',
    ],

    // Errors
    'errors' => [
        'role_not_found' => 'Peran tidak ditemukan',
        'permission_not_found' => 'Izin tidak ditemukan',
        'group_not_found' => 'Grup tidak ditemukan',
        'invalid_permission' => 'Izin tidak valid',
        'cannot_delete_role' => 'Tidak dapat menghapus peran: peran ditugaskan ke pengguna',
        'cannot_delete_permission' => 'Tidak dapat menghapus izin: izin ditugaskan ke peran',
    ],
];
