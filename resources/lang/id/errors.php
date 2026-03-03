<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Error Language Lines (Indonesian)
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for error messages and HTTP
    | status codes throughout the application.
    |
    */

    'http' => [
        '400' => 'Permintaan Buruk',
        '401' => 'Tidak Terotorisasi',
        '403' => 'Terlarang',
        '404' => 'Tidak Ditemukan',
        '405' => 'Metode Tidak Diizinkan',
        '408' => 'Waktu Permintaan Habis',
        '419' => 'Halaman Kedaluwarsa',
        '429' => 'Terlalu Banyak Permintaan',
        '500' => 'Kesalahan Server Internal',
        '502' => 'Gateway Buruk',
        '503' => 'Layanan Tidak Tersedia',
        '504' => 'Waktu Gateway Habis',
    ],

    'messages' => [
        '400' => 'Permintaan tidak dapat dipahami oleh server.',
        '401' => 'Anda tidak terotorisasi untuk mengakses sumber daya ini.',
        '403' => 'Anda tidak memiliki izin untuk mengakses sumber daya ini.',
        '404' => 'Halaman yang Anda cari tidak dapat ditemukan.',
        '405' => 'Metode tidak diizinkan untuk sumber daya ini.',
        '408' => 'Permintaan memakan waktu terlalu lama untuk diproses.',
        '419' => 'Sesi Anda telah kedaluwarsa. Silakan refresh dan coba lagi.',
        '429' => 'Terlalu banyak permintaan. Silakan perlambat.',
        '500' => 'Terjadi kesalahan di sisi kami. Silakan coba lagi nanti.',
        '502' => 'Gateway buruk. Silakan coba lagi nanti.',
        '503' => 'Layanan sementara tidak tersedia. Silakan coba lagi nanti.',
        '504' => 'Waktu gateway habis. Silakan coba lagi nanti.',
    ],

    'actions' => [
        'go_home' => 'Ke Beranda',
        'go_back' => 'Kembali',
        'try_again' => 'Coba Lagi',
        'contact_support' => 'Hubungi Dukungan',
        'refresh' => 'Refresh Halaman',
    ],

    'general' => [
        'error' => 'Kesalahan',
        'oops' => 'Ups!',
        'something_wrong' => 'Terjadi kesalahan',
        'try_again' => 'Silakan coba lagi',
        'contact_admin' => 'Jika masalah berlanjut, silakan hubungi administrator.',
    ],

    'validation' => [
        'failed' => 'Validasi gagal',
        'check_input' => 'Silakan periksa input Anda dan coba lagi.',
    ],

    'database' => [
        'connection_failed' => 'Koneksi database gagal',
        'query_failed' => 'Query database gagal',
    ],

    'file' => [
        'not_found' => 'File tidak ditemukan',
        'upload_failed' => 'Upload file gagal',
        'invalid_type' => 'Tipe file tidak valid',
        'too_large' => 'File terlalu besar',
    ],

    'permission' => [
        'denied' => 'Izin ditolak',
        'insufficient' => 'Anda tidak memiliki izin yang cukup',
    ],
];
