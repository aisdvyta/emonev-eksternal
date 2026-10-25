<?php

namespace Tests\Support;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

/**
 * Google Drive palsu di memori untuk tes (hanya endpoint yang dipakai GoogleDriveClient).
 * Folder: ['id' => ['name' => ...]]; file: ['id' => ['name', 'parent', 'content']].
 */
class FakeDrive
{
    public array $folders = [];
    public array $files = [];
    public int $requests = 0;
    private int $seq = 0;

    public static function install(): self
    {
        $d = new self();
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'tok', 'expires_in' => 3600]),
            'www.googleapis.com/*' => fn (Request $r) => $d->handle($r),
        ]);

        return $d;
    }

    /** Isi file milik folder pertama (helper tes). */
    public function folderId(string $name = 'halokip-sync'): ?string
    {
        foreach ($this->folders as $id => $f) {
            if ($f['name'] === $name) {
                return $id;
            }
        }

        return null;
    }

    public function addFolder(string $name = 'halokip-sync'): string
    {
        $id = 'fld' . (++$this->seq);
        $this->folders[$id] = ['name' => $name];

        return $id;
    }

    public function addFile(string $folder, string $name, string $content): string
    {
        $id = 'fil' . (++$this->seq);
        $this->files[$id] = ['name' => $name, 'parent' => $folder, 'content' => $content];

        return $id;
    }

    public function namesIn(string $folder): array
    {
        $n = [];
        foreach ($this->files as $f) {
            if ($f['parent'] === $folder) {
                $n[] = $f['name'];
            }
        }
        sort($n);

        return $n;
    }

    public function contentOf(string $folder, string $name): ?string
    {
        foreach ($this->files as $f) {
            if ($f['parent'] === $folder && $f['name'] === $name) {
                return $f['content'];
            }
        }

        return null;
    }

    public function handle(Request $r)
    {
        $this->requests++;
        $url = parse_url($r->url());
        $path = $url['path'];
        parse_str($url['query'] ?? '', $q);
        $method = $r->method();

        // Daftar / cari
        if ($method === 'GET' && $path === '/drive/v3/files') {
            $query = $q['q'] ?? '';
            if (str_contains($query, 'application/vnd.google-apps.folder')) {
                preg_match("/name = '(.*?)' and/", $query, $m);
                $out = [];
                foreach ($this->folders as $id => $f) {
                    if ($f['name'] === ($m[1] ?? null)) {
                        $out[] = ['id' => $id, 'name' => $f['name']];
                    }
                }

                return Http::response(['files' => $out]);
            }
            preg_match("/^'(.*?)' in parents/", $query, $m);
            $out = [];
            foreach ($this->files as $id => $f) {
                if ($f['parent'] === ($m[1] ?? null)) {
                    $out[] = ['id' => $id, 'name' => $f['name'], 'md5Checksum' => md5($f['content']), 'modifiedTime' => '2026-10-25T00:00:00Z'];
                }
            }

            return Http::response(['files' => $out]);
        }

        if ($method === 'POST' && $path === '/drive/v3/files') {
            $id = 'fld' . (++$this->seq);
            $this->folders[$id] = ['name' => $r['name']];

            return Http::response(['id' => $id]);
        }

        if ($method === 'GET' && preg_match('#^/drive/v3/files/([^/]+)$#', $path, $m)) {
            $f = $this->files[urldecode($m[1])] ?? null;

            return $f ? Http::response($f['content'], 200) : Http::response(['error' => ['message' => 'not found']], 404);
        }

        if ($method === 'DELETE' && preg_match('#^/drive/v3/files/([^/]+)$#', $path, $m)) {
            unset($this->files[urldecode($m[1])]);

            return Http::response('', 204);
        }

        if ($method === 'POST' && $path === '/upload/drive/v3/files') {
            $body = (string) $r->body();
            preg_match('/boundary=(\S+)/', $r->header('Content-Type')[0] ?? '', $b);
            $parts = explode('--' . $b[1], $body);
            $meta = json_decode(trim(explode("\r\n\r\n", $parts[1], 2)[1]), true);
            $content = substr(explode("\r\n\r\n", $parts[2], 2)[1], 0, -2);
            $id = $this->addFile($meta['parents'][0], $meta['name'], $content);

            return Http::response(['id' => $id]);
        }

        if ($method === 'PATCH' && preg_match('#^/upload/drive/v3/files/([^/]+)$#', $path, $m)) {
            $id = urldecode($m[1]);
            if (! isset($this->files[$id])) {
                return Http::response(['error' => ['message' => 'not found']], 404);
            }
            $this->files[$id]['content'] = (string) $r->body();

            return Http::response(['id' => $id]);
        }

        return Http::response(['error' => ['message' => "unhandled {$method} {$path}"]], 400);
    }
}
