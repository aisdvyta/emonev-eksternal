# HaloKIP – Portal Penilai Eksternal (satelit)

Salinan tampilan & kode **emonev-refactor** (pusat) khusus penilai eksternal, di-host di halokip.my.id.
Halaman, layout, sidebar, Livewire, dan aturan penilaian sama dengan pusat. Perbedaan hanya:

- Login memakai form yang sama dengan pusat (username/email + kata sandi, **tanpa OTP**, tanpa SSO).
  Hanya akun berperan `penilai_eksternal` yang bisa masuk.
- Data (akun penilai, penugasan, periode, satker, kuesioner) datang dari pusat lewat sinkronisasi (HTTP HMAC atau Google Drive).
- Nilai visitasi/wawancara disimpan lokal (`pending_sync`, `revision`), lalu diambil pusat yang memvalidasi ulang dan mengagregasi.
- Halaman "Penilaian Akhir" terisi setelah data periode selesai disinkronkan (tahap berikutnya).

## Pasang / perbarui di cPanel

1. Unggah & ekstrak kode ke folder aplikasi (vendor/node_modules tidak disertakan).
2. `composer install --no-dev --optimize-autoloader`
3. `npm ci && npm run build` (atau build di lokal lalu unggah `public/build`).
4. `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://halokip.my.id`, `DB_*`, `SYNC_SECRET`,
   `PUSAT_URL=https://halokip.web.bps.go.id`, serta `EXTERNAL_DRIVE_ENABLED` dan `GOOGLE_DRIVE_*` (lihat `docs/SINKRON-GOOGLE-DRIVE.md`).
5. `php artisan migrate --force` (menambah kolom sinkron, role, tabel Spatie) lalu `php artisan config:clear && php artisan view:clear`.
6. Cron: `* * * * * cd /home/USER/APP && php artisan schedule:run >> /dev/null 2>&1`
7. Dorong ulang data dari pusat: `php artisan satellite:push --with-passwords`, lalu `php artisan drive:sync` di satelit.

Tes: `php artisan test`.
