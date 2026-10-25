{{ match ($mode ?? 'offline') {
    'online' => 'Daring (pertemuan online)',
    'online_upload' => 'Daring (unggah bukti)',
    default => 'Kunjungan langsung',
} }}
