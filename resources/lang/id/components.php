<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Baris Terjemahan Komponen
    |--------------------------------------------------------------------------
    |
    | Baris bahasa berikut digunakan oleh berbagai komponen di seluruh
    | aplikasi. Anda bebas memodifikasi baris bahasa ini sesuai dengan
    | kebutuhan aplikasi Anda.
    |
    */

    'table' => [
        // DataTables specific translations
        'show' => 'Tampilkan',
        'filter' => 'Filter',
        'search_placeholder' => 'Cari...',
        'empty_state' => 'Tidak ada data',
        'yes' => 'Ya',
        'no' => 'Tidak',
        
        // DataTables language object
        'datatables' => [
            'processing' => 'Memproses...',
            'empty_table' => 'Tidak ada data di tabel',
            'zero_records' => 'Tidak ada data yang cocok',
            'info' => 'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',
            'info_empty' => 'Menampilkan 0 sampai 0 dari 0 data',
            'info_filtered' => '(disaring dari _MAX_ total data)',
            'search' => 'Cari:',
            'length_menu' => 'Tampilkan _MENU_ data',
            'ajax_error' => 'Gagal memuat data',
            'paginate' => [
                'first' => 'Pertama',
                'last' => 'Terakhir',
                'next' => 'Selanjutnya',
                'previous' => 'Sebelumnya',
            ],
        ],
        
        // Pencarian
        'search' => 'Cari...',
        'search_table' => 'Cari tabel',
        'search_in' => 'Cari di :column',
        'clear_search' => 'Hapus pencarian',

        // Pengurutan
        'sort_asc' => 'Urutkan naik',
        'sort_desc' => 'Urutkan turun',
        'unsorted' => 'Tidak diurutkan',
        'sort_active_singular' => 'pengurutan aktif',
        'sort_active_plural' => 'pengurutan aktif',
        'clear_sorting' => 'Hapus pengurutan',
        'sort_hint' => 'Klik untuk mengurutkan. Shift+klik untuk pengurutan multi-kolom.',

        // Paginasi
        'showing' => 'Menampilkan :from sampai :to dari :total entri',
        'first' => 'Pertama',
        'previous' => 'Sebelumnya',
        'next' => 'Selanjutnya',
        'last' => 'Terakhir',
        'page' => 'Halaman',
        'page_size' => 'Ukuran halaman',
        'per_page' => 'Per halaman',
        'of' => 'dari',
        'entries' => 'entri',

        // Filter
        'filters' => 'Filter',
        'filter_by' => 'Filter berdasarkan',
        'active_filters' => 'Filter aktif',
        'clear_filters' => 'Hapus filter',
        'clear_filter' => 'Hapus filter',
        'clear_all' => 'Hapus semua',
        'apply_filters' => 'Terapkan filter',
        'no_filters' => 'Tidak ada filter aktif',
        'all' => 'Semua',
        'select_date_range' => 'Pilih rentang tanggal',
        'min' => 'Min',
        'max' => 'Maks',

        // Seleksi
        'select_all' => 'Pilih semua',
        'deselect_all' => 'Batalkan pilihan semua',
        'selected_count' => ':count dipilih',
        'select_row' => 'Pilih baris',

        // Pluralisasi
        'items_count' => '{0} Tidak ada item|{1} :count item|[2,*] :count item',
        'rows_count' => '{0} Tidak ada baris|{1} :count baris|[2,*] :count baris',
        'entries_count' => '{0} Tidak ada entri|{1} :count entri|[2,*] :count entri',
        'selected_items' => '{0} Tidak ada item dipilih|{1} :count item dipilih|[2,*] :count item dipilih',
        'filters_active' => '{0} Tidak ada filter aktif|{1} :count filter aktif|[2,*] :count filter aktif',
        'columns_hidden' => '{0} Tidak ada kolom tersembunyi|{1} :count kolom tersembunyi|[2,*] :count kolom tersembunyi',
        'results_found' => '{0} Tidak ada hasil ditemukan|{1} :count hasil ditemukan|[2,*] :count hasil ditemukan',

        // Aksi
        'actions' => 'Aksi',
        'view' => 'Lihat',
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'delete_confirm' => 'Apakah Anda yakin ingin menghapus item ini?',
        'bulk_actions' => 'Aksi massal',
        'bulk_delete' => 'Hapus yang dipilih',
        'bulk_delete_confirm' => 'Apakah Anda yakin ingin menghapus :count item?',

        // Ekspor
        'export' => 'Ekspor',
        'export_excel' => 'Ekspor ke Excel',
        'export_csv' => 'Ekspor ke CSV',
        'export_pdf' => 'Ekspor ke PDF',
        'print' => 'Cetak',

        // Status
        'loading' => 'Memuat...',
        'loading_more' => 'Memuat lebih banyak data...',
        'no_data' => 'Tidak ada data tersedia',
        'error' => 'Terjadi kesalahan',
        'retry' => 'Coba lagi',
        'empty_title' => 'Tidak ada data tersedia',
        'empty_description' => 'Tidak ada catatan untuk ditampilkan',
        'all_data_loaded' => 'Semua data telah dimuat',

        // Visibilitas kolom
        'show_columns' => 'Tampilkan/Sembunyikan Kolom',
        'hide_columns' => 'Sembunyikan kolom',
        'show_all_columns' => 'Tampilkan semua kolom',
        'hide_all_columns' => 'Sembunyikan semua kolom',

        // Pengubahan ukuran kolom
        'resize_column' => 'Seret untuk mengubah ukuran. Klik dua kali untuk menyesuaikan otomatis.',
        'auto_fit' => 'Sesuaikan otomatis',

        // Tampilan kartu mobile
        'collapse' => 'Ciutkan',
        'expand' => 'Perluas',
        'show_less' => 'Tampilkan lebih sedikit',
        'show_more' => 'Tampilkan lebih banyak',

        // Lain-lain
        'refresh' => 'Muat ulang',
        'reset' => 'Atur ulang',
        'items' => 'item',
        'total' => 'Total',
        'rows' => 'baris',

        // Fallback JavaScript
        'library_not_loaded' => 'Library tabel tidak dimuat',
        'invalid_configuration' => 'Konfigurasi tabel tidak valid',
        'network_error' => 'Terjadi kesalahan jaringan',
        'timeout_error' => 'Waktu permintaan habis',
    ],

    'form' => [
        // Label umum
        'name' => 'Nama',
        'email' => 'Email',
        'password' => 'Kata Sandi',
        'confirm_password' => 'Konfirmasi Kata Sandi',
        'status' => 'Status',
        'description' => 'Deskripsi',
        'created_at' => 'Dibuat Pada',
        'updated_at' => 'Diperbarui Pada',

        // Tombol
        'submit' => 'Kirim',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'reset' => 'Atur Ulang',
        'back' => 'Kembali',

        // Validasi
        'required' => 'Bidang ini wajib diisi',
        'invalid_email' => 'Alamat email tidak valid',
        'invalid_format' => 'Format tidak valid',
        'min_length' => 'Panjang minimum adalah :min karakter',
        'max_length' => 'Panjang maksimum adalah :max karakter',
    ],

    'chart' => [
        // Jenis grafik
        'line' => 'Grafik Garis',
        'bar' => 'Grafik Batang',
        'pie' => 'Grafik Lingkaran',
        'area' => 'Grafik Area',
        'donut' => 'Grafik Donat',

        // Label umum
        'loading' => 'Memuat grafik...',
        'no_data' => 'Tidak ada data tersedia',
        'error' => 'Gagal memuat grafik',
        'retry' => 'Coba lagi',
    ],

    'locale_switcher' => [
        // Label UI
        'toggle' => 'Ganti bahasa',
        'current_locale' => 'Bahasa saat ini',
        'select_locale' => 'Pilih bahasa',
        'keyboard_hint' => 'Tekan Alt+L untuk membuka',

        // Pesan
        'switch_success' => 'Bahasa diubah ke :locale',
        'switch_failed' => 'Gagal mengubah bahasa',
        'invalid_locale' => 'Bahasa yang dipilih tidak valid',
        'locale_not_available' => 'Bahasa ini tidak tersedia',

        // Status loading
        'switching' => 'Mengubah bahasa...',
        'loading' => 'Memuat...',
    ],
];
