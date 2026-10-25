<?php

namespace App\Services\Drive;

use App\Models\SyncLog;
use App\Services\PendingScores;
use App\Services\ScoreAcknowledger;
use App\Services\SnapshotImporter;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Sinkronisasi lewat Google Drive (jalur cadangan bila server pusat tidak bisa memanggil API satelit).
 * Pusat menulis `snap-*.enc` dan `ack.enc`; satelit membacanya, lalu menulis `scores.enc`. Semua file terenkripsi.
 *
 * Satu siklus: (1) terapkan snapshot dari pusat, (2) terapkan ack nilai, (3) terbitkan nilai yang belum diambil pusat.
 */
class DriveSyncService
{
    public const SCORES_FILE = 'scores.enc';
    public const ACK_FILE = 'ack.enc';
    public const SNAP_PREFIX = 'snap-';
    private const SCORES_CACHE = 'drive.scores_hash';

    public function __construct(
        protected SnapshotImporter $importer,
        protected PendingScores $pending,
        protected ScoreAcknowledger $acknowledger,
        protected ?GoogleDriveClient $drive = null,
    ) {
        $this->drive ??= new GoogleDriveClient(
            config('external.drive.client_id'),
            config('external.drive.client_secret'),
            config('external.drive.refresh_token'),
            config('external.drive.folder_name'),
        );
    }

    /** @return array{ok: bool, message: string, snapshots: int, acked: int, published: int} */
    public function run(): array
    {
        $stats = ['snapshots' => 0, 'acked' => 0, 'published' => 0];

        try {
            if (! $this->drive->isConfigured()) {
                throw new RuntimeException('Konfigurasi Google Drive belum lengkap.');
            }

            $folder = $this->drive->findFolder();
            if (! $folder) {
                return ['ok' => true, 'message' => 'Folder Drive belum dibuat oleh pusat.'] + $stats;
            }

            $files = $this->drive->list($folder);

            $stats['snapshots'] = $this->applySnapshots($files);
            $stats['acked'] = $this->applyAck($files);
            $stats['published'] = $this->publishScores($folder, $files);
        } catch (Throwable $e) {
            SyncLog::record('drive', false, mb_substr($e->getMessage(), 0, 900));

            return ['ok' => false, 'message' => $e->getMessage()] + $stats;
        }

        return ['ok' => true, 'message' => "snapshot {$stats['snapshots']}, ack {$stats['acked']}, nilai terbit {$stats['published']}"] + $stats;
    }

    /** @param  array<int, array>  $files */
    private function applySnapshots(array $files): int
    {
        $applied = 0;

        foreach ($files as $f) {
            if (! str_starts_with($f['name'], self::SNAP_PREFIX)) {
                continue;
            }

            try {
                $message = $this->open($this->drive->download($f['id']), 'snapshot');
                $summary = $this->importer->import((array) ($message['payload'] ?? []));
                SyncLog::record('drive-snapshot', true, ($message['payload']['mode'] ?? 'delta') . ' ' . json_encode($summary));
                $this->drive->delete($f['id']);
                $applied++;
            } catch (InvalidArgumentException|MessageException $e) {
                // File rusak/asing/rahasia salah: catat dan buang agar antrean tidak macet (pusat mengirim ulang).
                SyncLog::record('drive-snapshot', false, mb_substr($e->getMessage(), 0, 900));
                $this->drive->delete($f['id']);
            }
            // Kegagalan lain (mis. DB) sengaja dilempar: file dipertahankan dan diulang di siklus berikutnya.
        }

        return $applied;
    }

    /** @param  array<int, array>  $files */
    private function applyAck(array $files): int
    {
        foreach ($files as $f) {
            if ($f['name'] !== self::ACK_FILE) {
                continue;
            }

            try {
                $message = $this->open($this->drive->download($f['id']), 'ack');
                $acked = $this->acknowledger->acknowledge((array) ($message['payload']['items'] ?? []));
                SyncLog::record('drive-ack', true, "acked={$acked}");
                $this->drive->delete($f['id']);

                return $acked;
            } catch (MessageException $e) {
                SyncLog::record('drive-ack', false, mb_substr($e->getMessage(), 0, 900));
                $this->drive->delete($f['id']);
            }
        }

        return 0;
    }

    /** @param  array<int, array>  $files  @return int jumlah nilai yang diterbitkan */
    private function publishScores(string $folder, array $files): int
    {
        $remote = null;
        foreach ($files as $f) {
            if ($f['name'] === self::SCORES_FILE) {
                $remote = $f;
            }
        }

        $items = $this->pending->collect((int) config('external.drive.scores_limit', 500))['items'];

        if ($items === []) {
            if ($remote) {
                $this->drive->delete($remote['id']);
            }
            Cache::forget(self::SCORES_CACHE);

            return 0;
        }

        $hash = sha1(json_encode($items));
        if ($remote && Cache::get(self::SCORES_CACHE) === $hash) {
            return 0; // sudah terbit dan tidak berubah
        }

        $blob = Envelope::seal([
            'v' => 1,
            'type' => 'scores',
            'created_at' => now()->toIso8601String(),
            'payload' => ['items' => $items],
        ], $this->secrets()[0] ?? '');

        $this->drive->put($folder, self::SCORES_FILE, $blob);
        Cache::forever(self::SCORES_CACHE, $hash);
        SyncLog::record('drive-scores', true, 'terbit ' . count($items) . ' nilai');

        return count($items);
    }

    /** @return array<string, mixed> */
    private function open(string $blob, string $expectedType): array
    {
        try {
            $message = Envelope::open($blob, $this->secrets());
        } catch (RuntimeException $e) {
            throw new MessageException($e->getMessage(), 0, $e);
        }

        if (($message['type'] ?? null) !== $expectedType) {
            throw new MessageException("Jenis pesan tidak sesuai (diharapkan {$expectedType}).");
        }

        return $message;
    }

    /** @return array<int, string> */
    private function secrets(): array
    {
        return (array) config('external.sync.secrets', []);
    }
}
