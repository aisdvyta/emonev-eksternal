# API Sinkronisasi Satelit ⇄ Sistem Pusat (v1)

Kontrak antara **portal satelit penilai eksternal** (`emonev-eksternal`, di internet) dan **HaloKIP pusat** (`emonev-refactor`, di jaringan BPS).

**Prinsip:** pusat selalu yang memulai koneksi (BPS → internet). Satelit tidak pernah memanggil pusat, jadi tidak ada port masuk yang perlu dibuka di jaringan BPS.

Basis URL: `https://<domain-satelit>/api/v1`

## 1. Autentikasi: tanda tangan HMAC-SHA256

Setiap request (termasuk GET) wajib membawa tiga header:

| Header | Isi |
|---|---|
| `X-Sync-Timestamp` | detik epoch UTC, mis. `1792859337` |
| `X-Sync-Nonce` | string acak 16–128 karakter, **unik per request** |
| `X-Sync-Signature` | `v1=` + hex(HMAC_SHA256(secret, canonical)) |

```
canonical = METODE + "\n" + REQUEST_URI + "\n" + timestamp + "\n" + nonce + "\n" + sha256_hex(body)
```

- `METODE` huruf besar (`GET`, `POST`).
- `REQUEST_URI` = path **beserta query string persis seperti dikirim** (mis. `/api/v1/sync/scores?x=1`). Bila satelit dipasang di sub-folder, sertakan sub-folder itu.
- `sha256_hex(body)` = SHA-256 dari byte body mentah; untuk GET tanpa body = SHA-256 dari string kosong.
- Selisih jam maksimal 300 detik (`SYNC_MAX_SKEW`). Nonce yang sama ditolak (anti replay).
- Rahasia (`SYNC_SECRET`) **tidak pernah dikirim**. Rotasi: isi rahasia lama di `SYNC_SECRET_PREVIOUS` di satelit, ganti `SYNC_SECRET`, deploy pusat dengan rahasia baru, lalu hapus rahasia lama.

Contoh klien PHP (sisi pusat):

```php
$body = json_encode($payload, JSON_UNESCAPED_UNICODE);
$uri  = '/api/v1/sync/snapshot';
$ts   = (string) time();
$nonce = bin2hex(random_bytes(16));
$canonical = implode("\n", ['POST', $uri, $ts, $nonce, hash('sha256', $body)]);

Http::withHeaders([
    'X-Sync-Timestamp' => $ts,
    'X-Sync-Nonce' => $nonce,
    'X-Sync-Signature' => 'v1='.hash_hmac('sha256', $canonical, $secret),
])->withBody($body, 'application/json')->timeout(60)->post($base.$uri);
```

Kode galat: `401 bad_headers | stale_timestamp | bad_signature | replayed_nonce`, `403 ip_not_allowed`, `503 sync_not_configured`, `429` (rate limit 120/menit per IP).

## 2. Endpoint

### `GET /ping`
`{"ok":true,"app":"emonev-eksternal","api_version":1,"time":"...","pending_scores":3}`. Dipakai untuk cek koneksi dan tanda tangan.

### `POST /sync/snapshot`: pusat → satelit
Menerapkan data. Idempoten dan atomik (satu transaksi).

```json
{
  "sync_id": "2026-10-25T10:00:00Z-001",
  "mode": "delta",
  "data": { "<tabel>": [ {baris}, ... ] },
  "deletions": { "<tabel>": [ key, ... ] },
  "prune": ["<tabel>", ...]
}
```

- `mode`: `delta` (default) atau `full`. `prune` **hanya berlaku pada `full`**: baris yang tidak ada di `data[tabel]` dihapus (tabel harus ikut dikirim di `data`, boleh kosong). Tabel `users` tidak boleh di-prune; nonaktifkan akun dengan `is_active:false`.
- `deletions`: tabel kunci tunggal → daftar nilai kunci; tabel kunci komposit (`submission_evaluators`, `visitasi_evaluators`, `interview_evaluators`) → daftar pasangan `[id_induk, id_user]`.
- Batas 20.000 baris per tabel. Tabel/kolom di luar daftar di bawah ditolak (`422`) atau diabaikan.

Tabel dan kolom yang diterima (kunci utama dicetak tebal):

| Tabel | Kolom |
|---|---|
| `users` | **id_user**, name, nama_user, email, is_active, password_hash (opsional, hanya bcrypt `$2y$`) |
| `periods` | **id_periode**, periode_start/end, saq_start/end, eval_start/end, visit_start/end, interview_start/end, result_start/end |
| `master_satker` | **kode_satker**, nama_satker |
| `master_indikator` | **id_indicators**, nama_indikator, deskripsi_indikator |
| `master_sub_indikator` | **id_sub_indicators**, id_indicators, nama_sub_indikator |
| `master_pertanyaan` | **id_master_pertanyaan**, order_no, id_sub_indicators, id_indicators, ques_text, ques_description, doc_requirement, link_drive, link_ppid, bobot_pertanyaan |
| `result` | **id_result**, kode_satker, id_periode (tanpa skor) |
| `submissions` | **id_submission**, id_result, status |
| `answer` | **id_answer**, id_submission, id_question, id_indicators, answer, link_ppid, link_drive (**tanpa** answer_score/evaluator_note) |
| `submission_evaluators` | **id_submission, id_user** |
| `visitasi` | **id_visit**, id_result, visit_mode, link_meet_visitasi, visit_at, link_buktidukung, is_final |
| `interview` | **id_interview**, id_result, link_meet_interview, interview_at, is_final |
| `visitasi_evaluators` | **id_visit, id_user**, score, catatan, is_draft |
| `interview_evaluators` | **id_interview, id_user**, score, catatan, is_draft |

Aturan penting:
- `is_final=true` pada `visitasi`/`interview` mengunci input di satelit. Pusat mengirimnya saat penilaian difinalisasi.
- **Baris `*_evaluators` dengan `pending_sync=true` di satelit (nilai penilai yang belum diambil pusat) tidak pernah ditimpa maupun dihapus oleh snapshot.** Ringkasannya ada di `summary.<tabel>.kept_local`.
- **Jangan kirim nilai/catatan evaluator lain.** Untuk `*_evaluators`, kirim hanya baris milik penilai eksternal.
- `password_hash`: kirim hanya saat akun baru dibuat atau saat admin pusat mereset kata sandi; kata sandi yang diganti penilai di satelit akan tertimpa jika pusat mengirim hash lagi.
- Tanggal: `YYYY-MM-DD` (ISO 8601 lengkap juga diterima, diambil bagian tanggalnya).

Respons: `{"ok":true,"sync_id":"...","summary":{"<tabel>":{"upserted":n,...}}}`. Galat: `422 invalid_payload|invalid_mode`, `500 server_error`.

### `GET /sync/scores`: satelit → pusat (ditarik pusat)
Nilai penilai yang belum diambil pusat. Idempoten: diulang akan mengembalikan hal yang sama sampai di-ack.

```json
{"ok":true,"generated_at":"...","has_more":false,"items":[
  {"type":"visitasi","parent_id":"<uuid id_visit>","id_user":123,"score":82.5,
   "catatan":"...","is_draft":false,"revision":3,"saved_at":"2026-10-25T10:01:00+07:00"},
  {"type":"interview","parent_id":30,"id_user":123,"score":null,"catatan":"...","is_draft":true,"revision":1,"saved_at":"..."}
]}
```
Maksimal 200 baris per tabel per panggilan; bila `has_more=true`, ulangi setelah ack.

### `POST /sync/ack`: pusat → satelit
Setelah pusat **berhasil menyimpan** nilai, kirim:
```json
{"items":[{"type":"visitasi","parent_id":"<uuid>","id_user":123,"revision":3}]}
```
Hanya baris dengan `revision` sama yang dibersihkan (`pending_sync=false`). Jika penilai menyimpan lagi setelah pull, revision-nya sudah naik, sehingga edit baru tidak hilang dan akan tertarik di siklus berikutnya. Respons: `{"ok":true,"acked":n}`.

## 3. Urutan siklus di pusat (disarankan, tiap 10–15 menit)

1. `GET /ping`: jika gagal, berhenti (jangan menghapus apa pun).
2. `POST /sync/snapshot` (delta; `full` + prune sekali sehari/saat penugasan berubah besar).
3. `GET /sync/scores`: untuk tiap item: **validasi ulang di pusat** (penugasan benar, tahap & tenggat, belum final), simpan lewat service evaluasi, lalu kumpulkan yang berhasil.
4. `POST /sync/ack` hanya untuk item yang **berhasil** disimpan. Item yang ditolak pusat jangan di-ack (kirim `is_final`/status terbaru lewat snapshot agar penilai tahu alasannya).
5. Ulangi langkah 3–4 selama `has_more=true`.
