<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Titik Depot Bank Sampah (Titik 0 Optimasi Rute — PBI-11)
    |--------------------------------------------------------------------------
    |
    | Digunakan sebagai titik keberangkatan dan kepulangan pada perhitungan
    | rute tertutup penjemputan sampah (Haversine + Nearest Neighbor + 2-Opt).
    | Default mengarah ke pusat Kota Yogyakarta.
    |
    */
    'bank_sampah' => [
        'name' => env('BANK_SAMPAH_NAME', 'Bank Sampah SetorKu Yogyakarta'),
        'lat' => (float) env('BANK_SAMPAH_LAT', -7.7970680),
        'lng' => (float) env('BANK_SAMPAH_LNG', 110.3705290),
    ],

    /*
    |--------------------------------------------------------------------------
    | Email Admin Awal pada Seeder (PBI-15)
    |--------------------------------------------------------------------------
    |
    | Memungkinkan pemilik proyek mengisi Gmail aslinya di SEED_ADMIN_EMAIL
    | agar akun Admin langsung cocok saat login menggunakan Google.
    |
    */
    'seed_admin_email' => env('SEED_ADMIN_EMAIL', 'admin@banksampah.test'),
];
