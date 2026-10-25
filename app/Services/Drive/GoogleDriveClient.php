<?php

namespace App\Services\Drive;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Klien minimal Google Drive API v3 dengan OAuth refresh token (scope drive.file).
 *
 * Scope drive.file hanya melihat file/folder yang dibuat oleh aplikasi OAuth yang sama, sehingga pusat dan satelit
 * HARUS memakai OAuth client + akun Google yang sama. Folder dibuat otomatis oleh pusat; satelit hanya mencarinya.
 */
class GoogleDriveClient
{
    public const API = 'https://www.googleapis.com';
    public const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const FOLDER_MIME = 'application/vnd.google-apps.folder';

    public function __construct(
        protected ?string $clientId = null,
        protected ?string $clientSecret = null,
        protected ?string $refreshToken = null,
        protected ?string $folderName = null,
        protected int $timeout = 60,
    ) {
    }

    public function isConfigured(): bool
    {
        return (string) $this->clientId !== '' && (string) $this->clientSecret !== ''
            && (string) $this->refreshToken !== '' && (string) $this->folderName !== '';
    }

    // ------------------------------------------------------------------ folder

    /** Folder tertua dengan nama yang dikonfigurasi (bila ada beberapa karena balapan pembuatan). */
    public function findFolder(): ?string
    {
        $q = "mimeType = '" . self::FOLDER_MIME . "' and name = '" . $this->esc((string) $this->folderName) . "' and trashed = false";
        $res = $this->json('GET', '/drive/v3/files', ['q' => $q, 'orderBy' => 'createdTime', 'pageSize' => 10, 'fields' => 'files(id,name,createdTime)']);

        return $res['files'][0]['id'] ?? null;
    }

    public function ensureFolder(): string
    {
        $id = $this->findFolder();
        if ($id) {
            return $id;
        }

        $res = $this->json('POST', '/drive/v3/files', ['fields' => 'id'], [
            'name' => $this->folderName,
            'mimeType' => self::FOLDER_MIME,
        ]);

        return (string) $res['id'];
    }

    // ------------------------------------------------------------------ file

    /**
     * @return array<int, array{id: string, name: string, md5Checksum: ?string, modifiedTime: ?string}>  urut nama naik
     */
    public function list(string $folderId, string $prefix = ''): array
    {
        $files = [];
        $page = null;
        do {
            $params = [
                'q' => "'" . $this->esc($folderId) . "' in parents and trashed = false",
                'pageSize' => 1000,
                'fields' => 'nextPageToken,files(id,name,md5Checksum,modifiedTime)',
            ];
            if ($page) {
                $params['pageToken'] = $page;
            }
            $res = $this->json('GET', '/drive/v3/files', $params);
            foreach ($res['files'] ?? [] as $f) {
                if ($prefix === '' || str_starts_with((string) $f['name'], $prefix)) {
                    $files[] = ['id' => $f['id'], 'name' => $f['name'], 'md5Checksum' => $f['md5Checksum'] ?? null, 'modifiedTime' => $f['modifiedTime'] ?? null];
                }
            }
            $page = $res['nextPageToken'] ?? null;
        } while ($page);

        usort($files, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $files;
    }

    public function download(string $fileId): string
    {
        $res = $this->send('GET', '/drive/v3/files/' . rawurlencode($fileId), ['alt' => 'media']);

        return $res->body();
    }

    public function create(string $folderId, string $name, string $content): string
    {
        $boundary = 'hk' . Str::lower(Str::random(24));
        $meta = json_encode(['name' => $name, 'parents' => [$folderId]], JSON_UNESCAPED_UNICODE);
        $body = "--{$boundary}\r\nContent-Type: application/json; charset=UTF-8\r\n\r\n{$meta}\r\n"
            . "--{$boundary}\r\nContent-Type: text/plain\r\n\r\n{$content}\r\n--{$boundary}--";

        $res = $this->send('POST', '/upload/drive/v3/files', ['uploadType' => 'multipart', 'fields' => 'id'], $body, "multipart/related; boundary={$boundary}");

        return (string) $res->json('id');
    }

    public function update(string $fileId, string $content): void
    {
        $this->send('PATCH', '/upload/drive/v3/files/' . rawurlencode($fileId), ['uploadType' => 'media'], $content, 'text/plain');
    }

    /** Tulis file bernama $name (ganti isi bila sudah ada). */
    public function put(string $folderId, string $name, string $content): string
    {
        foreach ($this->list($folderId, $name) as $f) {
            if ($f['name'] === $name) {
                $this->update($f['id'], $content);

                return $f['id'];
            }
        }

        return $this->create($folderId, $name, $content);
    }

    public function delete(string $fileId): void
    {
        $this->send('DELETE', '/drive/v3/files/' . rawurlencode($fileId));
    }

    // ------------------------------------------------------------------ http

    public function accessToken(): string
    {
        $key = 'google_drive_token_' . md5($this->clientId . '|' . $this->refreshToken);
        $cached = Cache::get($key);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $res = Http::asForm()->timeout($this->timeout)->post(self::TOKEN_URL, [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException('Tidak bisa menghubungi Google (token): ' . $e->getMessage(), 0, $e);
        }

        if (! $res->successful() || ! $res->json('access_token')) {
            $err = $res->json('error_description') ?? $res->json('error') ?? ('HTTP ' . $res->status());
            throw new RuntimeException('Gagal mengambil token Google: ' . $err);
        }

        $token = (string) $res->json('access_token');
        Cache::put($key, $token, max(60, (int) $res->json('expires_in', 3600) - 120));

        return $token;
    }

    /** @return array<string, mixed> */
    protected function json(string $method, string $path, array $query = [], ?array $json = null): array
    {
        $res = $this->send($method, $path, $query, $json === null ? null : json_encode($json, JSON_UNESCAPED_UNICODE), 'application/json');
        $data = $res->json();

        return is_array($data) ? $data : [];
    }

    protected function send(string $method, string $path, array $query = [], ?string $body = null, ?string $contentType = null): Response
    {
        $url = self::API . $path . ($query ? '?' . http_build_query($query) : '');
        $last = null;

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $req = $this->request();
            if ($body !== null) {
                $req = $req->withBody($body, $contentType ?? 'application/json');
            }

            try {
                $res = $req->send($method, $url);
            } catch (ConnectionException $e) {
                $last = new RuntimeException('Tidak bisa menghubungi Google Drive: ' . $e->getMessage(), 0, $e);
                continue;
            }

            if ($res->status() === 401 && $attempt === 1) {
                Cache::forget('google_drive_token_' . md5($this->clientId . '|' . $this->refreshToken));
                continue;
            }
            if ($res->status() === 429 || $res->serverError()) {
                $last = new RuntimeException("Google Drive membalas HTTP {$res->status()}.");
                continue;
            }
            if (! $res->successful()) {
                $msg = $res->json('error.message') ?? ('HTTP ' . $res->status());
                throw new RuntimeException("Google Drive menolak {$method} {$path}: {$msg}");
            }

            return $res;
        }

        throw $last ?? new RuntimeException("Gagal memanggil Google Drive {$path}.");
    }

    protected function request(): PendingRequest
    {
        return Http::withToken($this->accessToken())->acceptJson()->timeout($this->timeout);
    }

    private function esc(string $v): string
    {
        return str_replace(["\\", "'"], ["\\\\", "\\'"], $v);
    }
}
