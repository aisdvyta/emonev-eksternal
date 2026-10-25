<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Verifikasi kode login (OTP email)
    |--------------------------------------------------------------------------
    | Setelah kata sandi benar, penilai harus memasukkan kode 6 digit yang dikirim ke emailnya.
    */
    'login_otp' => (bool) env('EXTERNAL_LOGIN_OTP', true),
    'otp_ttl_minutes' => (int) env('EXTERNAL_OTP_TTL_MINUTES', 10),
    'otp_max_attempts' => 5,

    /*
    |--------------------------------------------------------------------------
    | API sinkronisasi dengan sistem pusat
    |--------------------------------------------------------------------------
    */
    'sync' => [
        // Rahasia aktif + rahasia lama (untuk rotasi kunci tanpa downtime).
        'secrets' => array_values(array_filter([
            env('SYNC_SECRET'),
            env('SYNC_SECRET_PREVIOUS'),
        ], fn ($v) => is_string($v) && $v !== '')),

        // Kosong = tidak membatasi IP. Isi bila IP keluar server BPS tetap.
        'allowed_ips' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SYNC_ALLOWED_IPS', ''))
        ))),

        // Toleransi selisih jam (detik) dan masa hidup nonce.
        'max_skew' => (int) env('SYNC_MAX_SKEW', 300),

        // Batas jumlah baris per tabel dalam satu snapshot.
        'max_rows_per_table' => 20000,

        // Batas jumlah baris nilai yang dikembalikan per pemanggilan scores.
        'scores_page_size' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sinkronisasi lewat Google Drive (jalur cadangan)
    |--------------------------------------------------------------------------
    | Dipakai bila server pusat tidak bisa memanggil API satelit. OAuth client + akun Google HARUS sama dengan pusat.
    | File dienkripsi (AES-256-GCM) dengan SYNC_SECRET.
    */
    'drive' => [
        'enabled' => (bool) env('EXTERNAL_DRIVE_ENABLED', false),
        'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
        'folder_name' => env('GOOGLE_DRIVE_FOLDER', 'halokip-sync'),
        'scores_limit' => 500,
        'poll_minutes' => (int) env('EXTERNAL_DRIVE_POLL_MINUTES', 2),
    ],
];
