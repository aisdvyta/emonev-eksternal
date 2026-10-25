# HaloKIP · Portal Satelit Penilai Eksternal (`emonev-eksternal`)

Aplikasi Laravel kecil yang dipasang di **hosting publik** supaya penilai eksternal (non-BPS, tanpa FortiClient) bisa mengisi penilaian **visitasi** dan **wawancara**, serta **melihat** kuesioner SAQ (hanya lihat). Data disalin dari sistem pusat (`emonev-refactor`) dan nilainya dikirim balik, semuanya lewat sinkronisasi yang **dimulai dari sisi BPS**.

```
 jaringan BPS (pusat)                                internet
┌───────────────────────┐   HTTPS + HMAC (keluar)  ┌──────────────────────┐
│ emonev-refactor       │ ───────────────────────► │ emonev-eksternal     │ ◄── penilai eksternal
│  scheduler 10-15 mnt  │   snapshot / ambil nilai │  (portal + API sync) │     (login + OTP email)
│  menghitung & final   │ ◄─────────────────────── │  hanya simpan nilai  │
└───────────────────────┘                          └──────────────────────┘
```

## Yang dilakukan & tidak dilakukan
- ✅ Penilai login (email/username + kata sandi, + kode OTP email), melihat penugasan, mengisi nilai & catatan visitasi/wawancara (draft atau kirim), melihat SAQ satker yang ditugaskan.
- ✅ Menyimpan nilai **milik penilai itu sendiri** + penanda `pending_sync`.
- ❌ Tidak menghitung rata-rata, mengubah status, atau memfinalisasi. Itu tugas pusat saat nilai ditarik.
- ❌ Tidak menyimpan skor/catatan evaluator lain, akun internal, SSO, pendaftaran mandiri, atau halaman hasil akhir (`/penilaian`) maupun ekspor Excel.

## Pasang di lokal
Butuh PHP ≥ 8.3 (8.4 dianjurkan), Composer, Node.js.

```bash
composer install
cp .env.example .env          # lalu atur DB_*, APP_URL, SYNC_SECRET
php artisan key:generate
php artisan migrate
npm install && npm run build
php artisan test              # 66 tes
```
Untuk coba lokal tanpa email: set `EXTERNAL_LOGIN_OTP=false` di `.env`.
Buat akun uji: `php artisan external:create-user 100 penilai@contoh.test "Nama Penilai"` (ID **harus sama** dengan `id_user` di pusat).

## Pasang di cPanel (halokip.my.id)
1. MultiPHP Manager: pilih PHP 8.3 atau lebih baru; aktifkan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `ctype`, `fileinfo`, `tokenizer`, `xml`.
2. Buat database MySQL + user. Isi `.env` dari `.env.example` (`APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://halokip.my.id`, `DB_*`, `MAIL_*`, `SYNC_SECRET`, `EXTERNAL_LOGIN_OTP=true`).
3. Unggah proyek **di luar** `public_html` (mis. `~/emonev-eksternal`), lalu arahkan *document root* domain ke `~/emonev-eksternal/public`. Jika document root tidak bisa diubah: salin isi `public/` ke `public_html` dan ubah path di `index.php` ke folder proyek. **Jangan** menaruh `.env`/`vendor` di folder yang bisa diakses web.
4. Di server (Terminal cPanel atau SSH): `composer install --no-dev --optimize-autoloader`, `php artisan key:generate --force`, `php artisan migrate --force`, `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
5. Jalankan `npm run build` di komputer lokal lalu unggah folder `public/build` (hosting biasanya tanpa Node).
6. Cron job (tiap menit): `* * * * * cd ~/emonev-eksternal && php artisan schedule:run >> /dev/null 2>&1`.
7. Pastikan HTTPS aktif (AutoSSL) dan `storage/` + `bootstrap/cache/` dapat ditulis.
8. Uji: `curl https://halokip.my.id/up` → 200. Lalu panggil `/api/v1/ping` dengan tanda tangan (lihat `docs/API-SINKRONISASI.md`).

## Perintah artisan
| Perintah | Fungsi |
|---|---|
| `external:create-user {id} {email} {nama}` | buat/ubah akun manual |
| `external:set-password {email}` | atur ulang kata sandi |
| `external:purge [id_periode]` | hapus data satker setelah periode selesai (menolak jika ada nilai belum tersinkron) |

## Keamanan (ringkas)
- API sinkronisasi: HMAC-SHA256 atas metode+URI+waktu+nonce+hash body, anti-replay, toleransi jam 5 menit, rate limit, opsi allowlist IP, rahasia ganda untuk rotasi.
- Login: throttle 5/menit per akun+IP, respons seragam (tidak membocorkan keberadaan akun), OTP email dengan batas percobaan, sesi terenkripsi, akun bisa dinonaktifkan dari pusat (berlaku seketika).
- Otorisasi: penilai hanya bisa membuka/mengisi penugasannya; SAQ tidak punya rute tulis; tautan dari satker hanya dirender jika `http(s)`.
- Header: CSP ditegakkan (tanpa inline script/CDN), HSTS, `X-Frame-Options: DENY`, `noindex`, `Cache-Control: no-store`.
- Minimisasi data: hanya penugasan penilai yang dikirim ke sini; hapus dengan `external:purge` setelah periode selesai.

Dokumen kontrak API: [`docs/API-SINKRONISASI.md`](docs/API-SINKRONISASI.md).
