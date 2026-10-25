# Sinkronisasi satelit lewat Google Drive

Dipakai bila server pusat (halokip.web.bps.go.id) **tidak bisa memanggil** `https://halokip.my.id` karena firewall BPS
(error `cURL error 35: Connection reset by peer`). Hasil uji menunjukkan `www.googleapis.com` dan
`oauth2.googleapis.com` bisa dijangkau dari server pusat, jadi folder Google Drive dipakai sebagai "kotak surat".

```
 server BPS (pusat)                Google Drive (folder halokip-sync)            halokip.my.id (satelit)
 satellite:sync  -- snap-*.enc ------------>  dibaca & dihapus  <-- drive:sync (tiap 2 menit)
                 <-- scores.enc ------------- nilai penilai  <-----
                 -- ack.enc ---------------->  dibaca & dihapus
```

Pusat tetap yang memulai semua koneksi. Semua file **dienkripsi + diautentikasi** (AES-256-GCM) dengan
`SATELLITE_SECRET` (pusat) = `SYNC_SECRET` (satelit); isi folder tidak bisa dibaca atau dipalsukan tanpa rahasia itu.

> **Izin dulu.** Data penugasan penilai (nama, email, hash kata sandi, nilai) akan lewat akun Google. Pakai akun Google
> **khusus** (bukan pribadi), dan pastikan atasan/pengelola data menyetujui, sama seperti hosting my.id.

## 1. Buat kredensial Google (sekali, di akun khusus tadi)

1. https://console.cloud.google.com → buat project (mis. `halokip-sync`).
2. **APIs & Services → Library** → aktifkan **Google Drive API**.
3. **OAuth consent screen** → External → isi nama aplikasi & email. Tambahkan scope
   `https://www.googleapis.com/auth/drive.file`. Lalu **Publish app** (status *In production*).
   **Penting:** bila dibiarkan *Testing*, refresh token kedaluwarsa tiap 7 hari dan sinkronisasi berhenti.
   Scope `drive.file` tidak perlu verifikasi Google.
4. **Credentials → Create credentials → OAuth client ID** → tipe *Web application*; Authorized redirect URI:
   `https://developers.google.com/oauthplayground`. Catat **Client ID** dan **Client secret**.
5. Buka https://developers.google.com/oauthplayground → ikon gerigi → centang **Use your own OAuth credentials**
   → isi Client ID/secret → di kolom scope ketik `https://www.googleapis.com/auth/drive.file` → **Authorize APIs**
   (login dengan akun khusus) → **Exchange authorization code for tokens** → salin **Refresh token**.

Scope `drive.file` hanya mengizinkan aplikasi mengakses file yang **ia buat sendiri**. Pusat dan satelit memakai
**Client ID yang sama**, jadi keduanya dianggap satu aplikasi dan sama-sama bisa membaca isi folder.

## 2. Isi `.env`

**Pusat** (`emonev-refactor`):

```
SATELLITE_ENABLED=true
SATELLITE_TRANSPORT=drive
SATELLITE_SECRET=<sama dengan SYNC_SECRET satelit>
SATELLITE_DRIVE_CLIENT_ID=...
SATELLITE_DRIVE_CLIENT_SECRET=...
SATELLITE_DRIVE_REFRESH_TOKEN=...
SATELLITE_DRIVE_FOLDER=halokip-sync
```

**Satelit** (`emonev-eksternal`, my.id):

```
SYNC_SECRET=<sama dengan SATELLITE_SECRET pusat>
EXTERNAL_DRIVE_ENABLED=true
GOOGLE_DRIVE_CLIENT_ID=...        # sama dengan pusat
GOOGLE_DRIVE_CLIENT_SECRET=...
GOOGLE_DRIVE_REFRESH_TOKEN=...
GOOGLE_DRIVE_FOLDER=halokip-sync
EXTERNAL_DRIVE_POLL_MINUTES=2
```

Lalu `php artisan config:clear` di keduanya. `SATELLITE_URL` tidak dipakai pada mode drive.

## 3. Jadwal

- **Satelit**: cron cPanel setiap menit
  `* * * * * cd /home/USER/emonev-eksternal && php artisan schedule:run >> /dev/null 2>&1`
  (menjalankan `drive:sync` tiap `EXTERNAL_DRIVE_POLL_MINUTES` menit).
- **Pusat**: jadwal `satellite:sync` yang sudah ada (tiap `SATELLITE_SCHEDULE_MINUTES`) otomatis memakai Drive.

## 4. Uji

1. Pusat: `php artisan satellite:push --with-passwords` → harus `Snapshot terkirim`; folder `halokip-sync` muncul di Drive.
2. Tunggu ≤ 2 menit (atau di satelit `php artisan drive:sync`) → file `snap-*.enc` hilang, akun penilai muncul di satelit.
3. Penilai mengisi nilai di my.id → `drive:sync` menerbitkan `scores.enc`.
4. Pusat: `php artisan satellite:pull` → nilai tersimpan; `ack.enc` muncul lalu dihapus satelit; baris `pending_sync` bersih.

## Catatan

- Hampir real-time (polling), bukan seketika: penundaan nilai ≈ interval polling kedua sisi.
- Bila pusat kelak diizinkan firewall ke `halokip.my.id`, cukup `SATELLITE_TRANSPORT=http`; data tidak perlu diubah.
- Rotasi rahasia: set `SYNC_SECRET_PREVIOUS` di satelit selama transisi.
- Bila refresh token dicabut/kedaluwarsa, `satellite:sync` melaporkan `Gagal mengambil token Google`; ulangi langkah 1.5.
- Jangan membagikan folder Drive ke orang lain; cukup akun khusus itu.
