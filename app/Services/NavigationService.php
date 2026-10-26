<?php

namespace App\Services;

use App\Models\MasterPembobotan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class NavigationService
{
    /**
     * Dapatkan URL buku panduan (dari file storage atau .env fallback).
     */
    public static function getPanduanUrl(): string
    {
        $activePeriod = \App\Models\Period::getActivePeriod();
        $link = $activePeriod?->masterLink?->link_panduan;
        if ($link) {
            return $link;
        }

        $panduanFile = storage_path('app/panduan_url.txt');
        $url = file_exists($panduanFile)
            ? trim(file_get_contents($panduanFile))
            : env('PANDUAN_DRIVE_URL', 'https://drive.google.com');

        return $url ?: 'https://drive.google.com';
    }

    /**
     * Dapatkan URL Google Drive review SAQ per periode aktif.
     */
    public static function getDriveReviewSaqUrl(): ?string
    {
        $activePeriod = \App\Models\Period::getActivePeriod();
        return $activePeriod?->masterLink?->link_drive_review_saq;
    }

    /**
     * Menentukan active role berdasarkan parameter sidebar view atau User model.
     */
    public static function resolveRole(?string $sidebarParam = null): string
    {
        $userRole = Auth::user()?->role?->nama_role;

        if ($sidebarParam && in_array($sidebarParam, ['admin', 'super_evaluator', 'evaluator', 'operator', 'verifikator', 'penilai_eksternal'])) {
            // Jika sidebarParam 'evaluator' tetapi user di DB adalah super_evaluator, utamakan super_evaluator
            if ($sidebarParam === 'evaluator' && $userRole === 'super_evaluator') {
                return 'super_evaluator';
            }
            return $sidebarParam;
        }

        return $userRole ?: 'operator';
    }

    /**
     * Mengambil urutan tahapan evaluasi aktif (SAQ, Visitasi, Wawancara).
     */
    public static function getStageOrder(): array
    {
        try {
            if (class_exists(MasterPembobotan::class) && method_exists(MasterPembobotan::class, 'getActiveStageOrder')) {
                return MasterPembobotan::getActiveStageOrder();
            }
        } catch (\Throwable $e) {
            // Fallback default
        }

        return ['saq', 'visitasi', 'wawancara'];
    }

    /** @var array<int, array<string, bool>> Cache in-request penugasan per user */
    protected static array $assignedStagesCache = [];

    /**
     * Clear cache penugasan per user (berguna untuk testing atau re-query).
     */
    public static function clearAssignedStagesCache(): void
    {
        self::$assignedStagesCache = [];
    }

    /**
     * Dapatkan daftar tahapan evaluasi yang ditugaskan kepada user (khususnya Penilai Eksternal).
     *
     * @return array<string, bool> ['saq' => bool, 'visitasi' => bool, 'wawancara' => bool]
     */
    public static function getAssignedStagesForUser(?int $userId = null): array
    {
        $userId = $userId ?: Auth::id();
        if (!$userId) {
            return ['saq' => false, 'visitasi' => false, 'wawancara' => false];
        }

        if (isset(self::$assignedStagesCache[$userId])) {
            return self::$assignedStagesCache[$userId];
        }

        $hasSaq = false;
        $hasVisitasi = false;
        $hasWawancara = false;

        try {
            $hasSaq = \App\Models\Submission::whereHas('evaluators', fn($q) => $q->where('users.id_user', $userId))->exists();
            $hasVisitasi = \App\Models\Visitasi::whereHas('evaluators', fn($q) => $q->where('id_user', $userId))->exists();
            $hasWawancara = \App\Models\Interview::whereHas('evaluators', fn($q) => $q->where('id_user', $userId))->exists();
        } catch (\Throwable $e) {
            // Silently fallback if table doesn't exist
        }

        $result = [
            'saq' => $hasSaq,
            'visitasi' => $hasVisitasi,
            'wawancara' => $hasWawancara,
        ];

        self::$assignedStagesCache[$userId] = $result;
        return $result;
    }

    /**
     * Dapatkan daftar menu sidebar sesuai role aktif.
     */
    public static function getSidebarItems(?string $sidebarParam = null): array
    {
        $role = self::resolveRole($sidebarParam);
        $stageOrder = self::getStageOrder();
        $urlPanduan = self::getPanduanUrl();

        switch ($role) {
            case 'admin':
                return [
                    [
                        'type' => 'link',
                        'label' => 'Beranda',
                        'icon' => 'home',
                        'href' => '/admin',
                        'active' => Request::is('admin', 'admin/', 'dashboard', 'dashboard/'),
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Manajemen User',
                        'icon' => 'users',
                        'href' => '/users',
                        'active' => Request::is('users*', 'master/users*', 'admin/users*'),
                    ],
                ];

            case 'super_evaluator':
                $stageLinks = [
                    'saq' => [
                        'href' => '/super-evaluator/monitor-kues',
                        'label' => 'Kuesioner SAQ',
                        'active' => Request::is('super-evaluator/monitor-kues*', 'evaluasi/saq*'),
                    ],
                    'visitasi' => [
                        'href' => '/super-evaluator/monitor-visitasi',
                        'label' => 'Visitasi & Verif. Faktual',
                        'active' => Request::is('super-evaluator/monitor-visitasi*', 'evaluasi/visitasi*'),
                    ],
                    'wawancara' => [
                        'href' => '/super-evaluator/monitor-wawancara',
                        'label' => 'Wawancara',
                        'active' => Request::is('super-evaluator/monitor-wawancara*', 'evaluasi/wawancara*'),
                    ],
                ];

                $monitoringChildren = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $monitoringChildren[] = $stageLinks[$stageKey];
                    }
                }
                $monitoringChildren[] = [
                    'href' => '/super-evaluator/penilaian',
                    'label' => 'Penilaian Akhir',
                    'active' => Request::is('super-evaluator/penilaian*', 'evaluasi/penilaian*'),
                ];

                return [
                    [
                        'type' => 'link',
                        'label' => 'Beranda',
                        'icon' => 'home',
                        'href' => '/super-evaluator',
                        'active' => Request::is('super-evaluator', 'super-evaluator/', 'dashboard', 'dashboard/'),
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Manajemen User',
                        'icon' => 'users',
                        'href' => '/users',
                        'active' => Request::is('users*', 'master/users*', 'super-evaluator/users*'),
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Manajemen Jadwal',
                        'icon' => 'calendar',
                        'href' => '/master/periode',
                        'active' => Request::is('master/periode*', 'super-evaluator/manage-jadwal*'),
                    ],
                    [
                        'type' => 'dropdown',
                        'label' => 'Master Kuesioner',
                        'icon' => 'database',
                        'active' => Request::is('master/pembobotan*', 'master/indikator*', 'master/sub-indikator*', 'master/kuesioner*', 'super-evaluator/manage-pembobotan*', 'super-evaluator/manage-indikator*', 'super-evaluator/manage-sub-indikator*', 'super-evaluator/manage-kuesioner*'),
                        'children' => [
                            ['href' => '/master/pembobotan', 'label' => 'Master Pembobotan', 'active' => Request::is('master/pembobotan*', 'super-evaluator/manage-pembobotan*')],
                            ['href' => '/master/indikator', 'label' => 'Master Indikator', 'active' => Request::is('master/indikator*', 'super-evaluator/manage-indikator*')],
                            ['href' => '/master/sub-indikator', 'label' => 'Master Sub-Indikator', 'active' => Request::is('master/sub-indikator*', 'super-evaluator/manage-sub-indikator*')],
                            ['href' => '/master/kuesioner', 'label' => 'Master Pertanyaan', 'active' => Request::is('master/kuesioner*', 'super-evaluator/manage-kuesioner*')],
                        ],
                    ],
                    [
                        'type' => 'dropdown',
                        'label' => 'Monitoring Kegiatan',
                        'icon' => 'chart-bar',
                        'active' => Request::is('super-evaluator/monitor-kues*', 'evaluasi/saq*', 'super-evaluator/monitor-visitasi*', 'evaluasi/visitasi*', 'super-evaluator/monitor-wawancara*', 'evaluasi/wawancara*', 'super-evaluator/penilaian*', 'evaluasi/penilaian*'),
                        'children' => $monitoringChildren,
                    ],
                    [
                        'type' => 'link',
                        'label' => 'SK & Sertifikat',
                        'icon' => 'document',
                        'href' => '/dokumen/manage-sk',
                        'active' => Request::is('dokumen/manage-sk*', 'super-evaluator/manage-sk*'),
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Upload Panduan',
                        'icon' => 'book-open',
                        'href' => '/dokumen/manage-panduan',
                        'active' => Request::is('dokumen/manage-panduan*', 'super-evaluator/manage-panduan*'),
                    ],
                ];

            case 'evaluator':
                $stageLinks = [
                    'saq' => [
                        'href' => '/evaluator/monitor-kues',
                        'label' => 'Kuesioner SAQ',
                        'active' => Request::is('evaluator/monitor-kues*', 'evaluator/review-kues*', 'evaluasi/saq*'),
                    ],
                    'visitasi' => [
                        'href' => '/evaluator/monitor-visitasi',
                        'label' => 'Visitasi & Verif. Faktual',
                        'active' => Request::is('evaluator/monitor-visitasi*', 'evaluator/review-visitasi*', 'evaluasi/visitasi*'),
                    ],
                    'wawancara' => [
                        'href' => '/evaluator/monitor-wawancara',
                        'label' => 'Wawancara',
                        'active' => Request::is('evaluator/monitor-wawancara*', 'evaluator/review-wawancara*', 'evaluasi/wawancara*'),
                    ],
                ];

                $monitoringChildren = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $monitoringChildren[] = $stageLinks[$stageKey];
                    }
                }
                $monitoringChildren[] = [
                    'href' => '/evaluator/penilaian',
                    'label' => 'Penilaian Akhir',
                    'active' => Request::is('evaluator/penilaian*', 'evaluasi/penilaian*'),
                ];

                return [
                    [
                        'type' => 'link',
                        'label' => 'Beranda',
                        'icon' => 'home',
                        'href' => '/evaluator',
                        'active' => Request::is('evaluator', 'evaluator/', 'dashboard', 'dashboard/'),
                    ],
                    [
                        'type' => 'dropdown',
                        'label' => 'Master Kuesioner',
                        'icon' => 'database',
                        'active' => Request::is('master/indikator*', 'master/sub-indikator*', 'master/kuesioner*', 'evaluator/manage-indikator*', 'evaluator/manage-sub-indikator*', 'evaluator/manage-kuesioner*'),
                        'children' => [
                            ['href' => '/master/indikator', 'label' => 'Master Indikator', 'active' => Request::is('master/indikator*', 'evaluator/manage-indikator*')],
                            ['href' => '/master/sub-indikator', 'label' => 'Master Sub-Indikator', 'active' => Request::is('master/sub-indikator*', 'evaluator/manage-sub-indikator*')],
                            ['href' => '/master/kuesioner', 'label' => 'Master Pertanyaan', 'active' => Request::is('master/kuesioner*', 'evaluator/manage-kuesioner*')],
                        ],
                    ],
                    [
                        'type' => 'dropdown',
                        'label' => 'Monitoring Kegiatan',
                        'icon' => 'chart-bar',
                        'active' => Request::is('evaluator/monitor-kues*', 'evaluator/review-kues*', 'evaluasi/saq*', 'evaluator/monitor-visitasi*', 'evaluator/review-visitasi*', 'evaluasi/visitasi*', 'evaluator/monitor-wawancara*', 'evaluator/review-wawancara*', 'evaluasi/wawancara*', 'evaluator/penilaian*', 'evaluasi/penilaian*'),
                        'children' => $monitoringChildren,
                    ],
                    [
                        'type' => 'link',
                        'label' => 'SK & Sertifikat',
                        'icon' => 'document',
                        'href' => '/dokumen/manage-sk',
                        'active' => Request::is('dokumen/manage-sk*', 'evaluator/manage-sk*'),
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Panduan',
                        'icon' => 'book-open',
                        'href' => $urlPanduan,
                        'target' => '_blank',
                        'active' => false,
                    ],
                ];

            case 'operator':
                $stageLinks = [
                    'saq' => [
                        'href' => '/operator/kues-saq',
                        'label' => 'Kuesioner SAQ',
                        'active' => Request::is('operator/kues-saq*', 'evaluasi/saq*'),
                    ],
                    'visitasi' => [
                        'href' => '/operator/visitasi',
                        'label' => 'Visitasi & Verif. Faktual',
                        'active' => Request::is('operator/visitasi*', 'evaluasi/visitasi*'),
                    ],
                    'wawancara' => [
                        'href' => '/operator/wawancara',
                        'label' => 'Wawancara',
                        'active' => Request::is('operator/wawancara*', 'evaluasi/wawancara*'),
                    ],
                ];

                $evaluasiChildren = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $evaluasiChildren[] = $stageLinks[$stageKey];
                    }
                }
                $evaluasiChildren[] = [
                    'href' => '/operator/penilaian',
                    'label' => 'Penilaian Akhir',
                    'active' => Request::is('operator/penilaian*', 'evaluasi/penilaian*'),
                ];

                return [
                    [
                        'type' => 'link',
                        'label' => 'Beranda',
                        'icon' => 'home',
                        'href' => '/operator',
                        'active' => Request::is('operator', 'operator/', 'dashboard', 'dashboard/'),
                    ],
                    [
                        'type' => 'dropdown',
                        'label' => 'Kegiatan Evaluasi',
                        'icon' => 'clipboard-list',
                        'active' => Request::is('operator/kues-saq*', 'evaluasi/saq*', 'operator/visitasi*', 'evaluasi/visitasi*', 'operator/wawancara*', 'evaluasi/wawancara*', 'operator/penilaian*', 'evaluasi/penilaian*'),
                        'children' => $evaluasiChildren,
                    ],
                    [
                        'type' => 'link',
                        'label' => 'SK & Sertifikat',
                        'icon' => 'document',
                        'href' => '/dokumen/sk-sertifikat',
                        'active' => Request::is('dokumen/sk-sertifikat*', 'operator/sk-sertifikat*'),
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Panduan',
                        'icon' => 'book-open',
                        'href' => $urlPanduan,
                        'target' => '_blank',
                        'active' => false,
                    ],
                ];

            case 'verifikator':
                $stageLinks = [
                    'saq' => [
                        'href' => '/verifikator/verifikasi-saq',
                        'label' => 'Verifikasi SAQ',
                        'active' => Request::is('verifikator/verifikasi-saq*', 'verifikator/monitoring-saq*', 'evaluasi/saq*'),
                    ],
                    'visitasi' => [
                        'href' => '/verifikator/monitoring-visitasi',
                        'label' => 'Monitoring Visitasi',
                        'active' => Request::is('verifikator/monitoring-visitasi*', 'evaluasi/visitasi*'),
                    ],
                    'wawancara' => [
                        'href' => '/verifikator/monitoring-wawancara',
                        'label' => 'Monitoring Wawancara',
                        'active' => Request::is('verifikator/monitoring-wawancara*', 'evaluasi/wawancara*'),
                    ],
                ];

                $verifikasiChildren = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $verifikasiChildren[] = $stageLinks[$stageKey];
                    }
                }
                $verifikasiChildren[] = [
                    'href' => '/verifikator/penilaian',
                    'label' => 'Penilaian Akhir',
                    'active' => Request::is('verifikator/penilaian*', 'evaluasi/penilaian*'),
                ];

                return [
                    [
                        'type' => 'link',
                        'label' => 'Beranda',
                        'icon' => 'home',
                        'href' => '/verifikator',
                        'active' => Request::is('verifikator', 'verifikator/', 'dashboard', 'dashboard/'),
                    ],
                    [
                        'type' => 'dropdown',
                        'label' => 'Monitoring Kegiatan',
                        'icon' => 'chart-bar',
                        'active' => Request::is('verifikator/verifikasi-saq*', 'verifikator/monitoring-saq*', 'evaluasi/saq*', 'verifikator/monitoring-visitasi*', 'evaluasi/visitasi*', 'verifikator/monitoring-wawancara*', 'evaluasi/wawancara*', 'verifikator/penilaian*', 'evaluasi/penilaian*'),
                        'children' => $verifikasiChildren,
                    ],
                    [
                        'type' => 'link',
                        'label' => 'SK & Sertifikat',
                        'icon' => 'document',
                        'href' => '/dokumen/sk-sertifikat',
                        'active' => Request::is('dokumen/sk-sertifikat*', 'verifikator/sk-sertifikat*'),
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Panduan',
                        'icon' => 'book-open',
                        'href' => $urlPanduan,
                        'target' => '_blank',
                        'active' => false,
                    ],
                ];

            case 'penilai_eksternal':
                $assigned = self::getAssignedStagesForUser();

                $stageLinks = [
                    'saq' => [
                        'href' => '/penilai-eksternal/monitor-kues',
                        'label' => 'Kuesioner SAQ',
                        'active' => Request::is('penilai-eksternal/monitor-kues*', 'penilai-eksternal/review-kues*', 'evaluasi/saq*'),
                    ],
                    'visitasi' => [
                        'href' => '/penilai-eksternal/monitor-visitasi',
                        'label' => 'Visitasi & Verif. Faktual',
                        'active' => Request::is('penilai-eksternal/monitor-visitasi*', 'penilai-eksternal/review-visitasi*', 'evaluasi/visitasi*'),
                    ],
                    'wawancara' => [
                        'href' => '/penilai-eksternal/monitor-wawancara',
                        'label' => 'Wawancara',
                        'active' => Request::is('penilai-eksternal/monitor-wawancara*', 'penilai-eksternal/review-wawancara*', 'evaluasi/wawancara*'),
                    ],
                ];

                $monitoringChildren = [];
                foreach ($stageOrder as $stageKey) {
                    if (!empty($assigned[$stageKey]) && isset($stageLinks[$stageKey])) {
                        $monitoringChildren[] = $stageLinks[$stageKey];
                    }
                }
                $monitoringChildren[] = [
                    'href' => '/penilai-eksternal/penilaian',
                    'label' => 'Penilaian Akhir',
                    'active' => Request::is('penilai-eksternal/penilaian*', 'evaluasi/penilaian*'),
                ];

                return [
                    [
                        'type' => 'link',
                        'label' => 'Beranda',
                        'icon' => 'home',
                        'href' => '/penilai-eksternal',
                        'active' => Request::is('penilai-eksternal', 'penilai-eksternal/', 'dashboard', 'dashboard/'),
                    ],
                    [
                        'type' => 'dropdown',
                        'label' => 'Monitoring Kegiatan',
                        'icon' => 'chart-bar',
                        'active' => Request::is(
                            'penilai-eksternal/monitor-kues*',
                            'penilai-eksternal/review-kues*',
                            'penilai-eksternal/monitor-visitasi*',
                            'penilai-eksternal/review-visitasi*',
                            'penilai-eksternal/monitor-wawancara*',
                            'penilai-eksternal/review-wawancara*',
                            'penilai-eksternal/penilaian*',
                            'evaluasi/*'
                        ),
                        'children' => $monitoringChildren,
                    ],
                    [
                        'type' => 'link',
                        'label' => 'Panduan',
                        'icon' => 'book-open',
                        'href' => $urlPanduan,
                        'target' => '_blank',
                        'active' => false,
                    ],
                ];

            default:
                return [];
        }
    }

    /**
     * Dapatkan konfigurasi Bottom Navigation Mobile sesuai role aktif.
     */
    public static function getBottomNavConfig(?string $sidebarParam = null): array
    {
        $role = self::resolveRole($sidebarParam);
        $stageOrder = self::getStageOrder();
        $urlPanduan = self::getPanduanUrl();

        switch ($role) {
            case 'operator':
                $stageLinks = [
                    'saq' => ['href' => '/operator/kues-saq', 'label' => 'Kuesioner SAQ', 'pattern' => 'operator/kues-saq*,evaluasi/saq*'],
                    'visitasi' => ['href' => '/operator/visitasi', 'label' => 'Visitasi & Verif. Faktual', 'pattern' => 'operator/visitasi*,evaluasi/visitasi*'],
                    'wawancara' => ['href' => '/operator/wawancara', 'label' => 'Wawancara', 'pattern' => 'operator/wawancara*,evaluasi/wawancara*'],
                ];
                $evalLinks = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $evalLinks[] = $stageLinks[$stageKey];
                    }
                }
                $evalLinks[] = ['href' => '/operator/penilaian', 'label' => 'Penilaian Akhir', 'pattern' => 'operator/penilaian*,evaluasi/penilaian*'];

                return [
                    'role' => $role,
                    'sheets' => [
                        'evaluasi' => [
                            'title' => 'Kegiatan Evaluasi',
                            'links' => $evalLinks,
                        ],
                    ],
                    'tabs' => [
                        [
                            'type' => 'link',
                            'label' => 'Beranda',
                            'href' => '/operator',
                            'icon' => 'home',
                            'active' => Request::is('operator', 'operator/', 'dashboard', 'dashboard/'),
                        ],
                        [
                            'type' => 'link',
                            'label' => 'SK & Sertifikat',
                            'href' => '/dokumen/sk-sertifikat',
                            'icon' => 'document',
                            'active' => Request::is('dokumen/sk-sertifikat*', 'operator/sk-sertifikat*'),
                        ],
                        [
                            'type' => 'fab',
                            'label' => 'Evaluasi',
                            'sheet' => 'evaluasi',
                            'icon' => 'clipboard-list',
                            'active' => Request::is('operator/kues-saq*', 'evaluasi/saq*', 'operator/visitasi*', 'evaluasi/visitasi*', 'operator/wawancara*', 'evaluasi/wawancara*', 'operator/penilaian*', 'evaluasi/penilaian*'),
                        ],
                        [
                            'type' => 'link',
                            'label' => 'Panduan',
                            'href' => $urlPanduan,
                            'icon' => 'book-open',
                            'target' => '_blank',
                            'active' => false,
                        ],
                        [
                            'type' => 'logout',
                            'label' => 'Keluar',
                            'icon' => 'logout',
                        ],
                    ],
                ];

            case 'verifikator':
                $stageLinks = [
                    'saq' => ['href' => '/verifikator/verifikasi-saq', 'label' => 'Verifikasi SAQ', 'pattern' => 'verifikator/verifikasi-saq*,verifikator/monitoring-saq*,evaluasi/saq*'],
                    'visitasi' => ['href' => '/verifikator/monitoring-visitasi', 'label' => 'Monitoring Visitasi', 'pattern' => 'verifikator/monitoring-visitasi*,evaluasi/visitasi*'],
                    'wawancara' => ['href' => '/verifikator/monitoring-wawancara', 'label' => 'Monitoring Wawancara', 'pattern' => 'verifikator/monitoring-wawancara*,evaluasi/wawancara*'],
                ];
                $verifLinks = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $verifLinks[] = $stageLinks[$stageKey];
                    }
                }
                $verifLinks[] = ['href' => '/verifikator/penilaian', 'label' => 'Penilaian Akhir', 'pattern' => 'verifikator/penilaian*,evaluasi/penilaian*'];

                return [
                    'role' => $role,
                    'sheets' => [
                        'verifikasi' => [
                            'title' => 'Monitoring Kegiatan',
                            'links' => $verifLinks,
                        ],
                    ],
                    'tabs' => [
                        [
                            'type' => 'link',
                            'label' => 'Beranda',
                            'href' => '/verifikator',
                            'icon' => 'home',
                            'active' => Request::is('verifikator', 'verifikator/', 'dashboard', 'dashboard/'),
                        ],
                        [
                            'type' => 'link',
                            'label' => 'SK & Sertifikat',
                            'href' => '/dokumen/sk-sertifikat',
                            'icon' => 'document',
                            'active' => Request::is('dokumen/sk-sertifikat*', 'verifikator/sk-sertifikat*'),
                        ],
                        [
                            'type' => 'fab',
                            'label' => 'Verifikasi',
                            'sheet' => 'verifikasi',
                            'icon' => 'chart-bar',
                            'active' => Request::is('verifikator/verifikasi-saq*', 'verifikator/monitoring-saq*', 'evaluasi/saq*', 'verifikator/monitoring-visitasi*', 'evaluasi/visitasi*', 'verifikator/monitoring-wawancara*', 'evaluasi/wawancara*', 'verifikator/penilaian*', 'evaluasi/penilaian*'),
                        ],
                        [
                            'type' => 'link',
                            'label' => 'Panduan',
                            'href' => $urlPanduan,
                            'icon' => 'book-open',
                            'target' => '_blank',
                            'active' => false,
                        ],
                        [
                            'type' => 'logout',
                            'label' => 'Keluar',
                            'icon' => 'logout',
                        ],
                    ],
                ];

            case 'evaluator':
                $stageLinks = [
                    'saq' => ['href' => '/evaluator/monitor-kues', 'label' => 'Kuesioner SAQ', 'pattern' => 'evaluator/monitor-kues*,evaluator/review-kues*,evaluasi/saq*'],
                    'visitasi' => ['href' => '/evaluator/monitor-visitasi', 'label' => 'Visitasi & Verif. Faktual', 'pattern' => 'evaluator/monitor-visitasi*,evaluator/review-visitasi*,evaluasi/visitasi*'],
                    'wawancara' => ['href' => '/evaluator/monitor-wawancara', 'label' => 'Wawancara', 'pattern' => 'evaluator/monitor-wawancara*,evaluasi/wawancara*'],
                ];
                $monLinks = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $monLinks[] = $stageLinks[$stageKey];
                    }
                }
                $monLinks[] = ['href' => '/evaluator/penilaian', 'label' => 'Penilaian Akhir', 'pattern' => 'evaluator/penilaian*,evaluasi/penilaian*'];

                return [
                    'role' => $role,
                    'sheets' => [
                        'master' => [
                            'title' => 'Master Kuesioner',
                            'links' => [
                                ['href' => '/master/indikator', 'label' => 'Master Indikator', 'pattern' => 'master/indikator*'],
                                ['href' => '/master/sub-indikator', 'label' => 'Master Sub-Indikator', 'pattern' => 'master/sub-indikator*'],
                                ['href' => '/master/kuesioner', 'label' => 'Master Pertanyaan', 'pattern' => 'master/kuesioner*'],
                            ],
                        ],
                        'monitoring' => [
                            'title' => 'Monitoring Kegiatan',
                            'links' => $monLinks,
                        ],
                    ],
                    'tabs' => [
                        [
                            'type' => 'link',
                            'label' => 'Beranda',
                            'href' => '/evaluator',
                            'icon' => 'home',
                            'active' => Request::is('evaluator', 'evaluator/', 'dashboard', 'dashboard/'),
                        ],
                        [
                            'type' => 'sheet',
                            'label' => 'Master',
                            'sheet' => 'master',
                            'icon' => 'database',
                            'active' => Request::is('master/indikator*', 'master/sub-indikator*', 'master/kuesioner*', 'evaluator/manage-indikator*', 'evaluator/manage-sub-indikator*', 'evaluator/manage-kuesioner*'),
                        ],
                        [
                            'type' => 'fab',
                            'label' => 'Monitoring',
                            'sheet' => 'monitoring',
                            'icon' => 'chart-bar',
                            'active' => Request::is('evaluator/monitor-kues*', 'evaluator/review-kues*', 'evaluasi/saq*', 'evaluator/monitor-visitasi*', 'evaluator/review-visitasi*', 'evaluasi/visitasi*', 'evaluator/monitor-wawancara*', 'evaluator/review-wawancara*', 'evaluasi/wawancara*', 'evaluator/penilaian*', 'evaluasi/penilaian*'),
                        ],
                        [
                            'type' => 'link',
                            'label' => 'SK/Sertif',
                            'href' => '/dokumen/manage-sk',
                            'icon' => 'document',
                            'active' => Request::is('dokumen/manage-sk*', 'evaluator/manage-sk*'),
                        ],
                        [
                            'type' => 'logout',
                            'label' => 'Keluar',
                            'icon' => 'logout',
                        ],
                    ],
                ];

            case 'super_evaluator':
                $stageLinks = [
                    'saq' => ['href' => '/super-evaluator/monitor-kues', 'label' => 'Kuesioner SAQ', 'pattern' => 'super-evaluator/monitor-kues*,evaluasi/saq*'],
                    'visitasi' => ['href' => '/super-evaluator/monitor-visitasi', 'label' => 'Visitasi & Verif. Faktual', 'pattern' => 'super-evaluator/monitor-visitasi*,evaluasi/visitasi*'],
                    'wawancara' => ['href' => '/super-evaluator/monitor-wawancara', 'label' => 'Wawancara', 'pattern' => 'super-evaluator/monitor-wawancara*,evaluasi/wawancara*'],
                ];
                $monLinks = [];
                foreach ($stageOrder as $stageKey) {
                    if (isset($stageLinks[$stageKey])) {
                        $monLinks[] = $stageLinks[$stageKey];
                    }
                }
                $monLinks[] = ['href' => '/super-evaluator/penilaian', 'label' => 'Penilaian Akhir', 'pattern' => 'super-evaluator/penilaian*,evaluasi/penilaian*'];

                return [
                    'role' => $role,
                    'sheets' => [
                        'manajemen' => [
                            'title' => 'Manajemen & Master',
                            'sections' => [
                                [
                                    'title' => 'Manajemen',
                                    'links' => [
                                        ['href' => '/users', 'label' => 'Manajemen User', 'pattern' => 'users*,master/users*'],
                                        ['href' => '/master/periode', 'label' => 'Manajemen Jadwal', 'pattern' => 'master/periode*'],
                                    ],
                                ],
                                [
                                    'title' => 'Master Kuesioner',
                                    'links' => [
                                        ['href' => '/master/pembobotan', 'label' => 'Master Pembobotan', 'pattern' => 'master/pembobotan*'],
                                        ['href' => '/master/indikator', 'label' => 'Master Indikator', 'pattern' => 'master/indikator*'],
                                        ['href' => '/master/sub-indikator', 'label' => 'Master Sub-Indikator', 'pattern' => 'master/sub-indikator*'],
                                        ['href' => '/master/kuesioner', 'label' => 'Master Pertanyaan', 'pattern' => 'master/kuesioner*'],
                                    ],
                                ],
                            ],
                        ],
                        'monitoring' => [
                            'title' => 'Monitoring Kegiatan',
                            'links' => $monLinks,
                        ],
                    ],
                    'tabs' => [
                        [
                            'type' => 'link',
                            'label' => 'Beranda',
                            'href' => '/super-evaluator',
                            'icon' => 'home',
                            'active' => Request::is('super-evaluator', 'super-evaluator/', 'dashboard', 'dashboard/'),
                        ],
                        [
                            'type' => 'sheet',
                            'label' => 'Manajemen',
                            'sheet' => 'manajemen',
                            'icon' => 'database',
                            'active' => Request::is('users*', 'master/*', 'super-evaluator/users*', 'super-evaluator/manage-jadwal*', 'super-evaluator/manage-pembobotan*', 'super-evaluator/manage-indikator*', 'super-evaluator/manage-sub-indikator*', 'super-evaluator/manage-kuesioner*'),
                        ],
                        [
                            'type' => 'fab',
                            'label' => 'Monitoring',
                            'sheet' => 'monitoring',
                            'icon' => 'chart-bar',
                            'active' => Request::is('super-evaluator/monitor-kues*', 'evaluasi/saq*', 'super-evaluator/monitor-visitasi*', 'evaluasi/visitasi*', 'super-evaluator/monitor-wawancara*', 'evaluasi/wawancara*', 'super-evaluator/penilaian*', 'evaluasi/penilaian*'),
                        ],
                        [
                            'type' => 'link',
                            'label' => 'SK/Sertif',
                            'href' => '/dokumen/manage-sk',
                            'icon' => 'document',
                            'active' => Request::is('dokumen/manage-sk*', 'super-evaluator/manage-sk*'),
                        ],
                        [
                            'type' => 'logout',
                            'label' => 'Keluar',
                            'icon' => 'logout',
                        ],
                    ],
                ];

            case 'penilai_eksternal':
                $assigned = self::getAssignedStagesForUser();
                $stageLinks = [
                    'saq' => ['href' => '/penilai-eksternal/monitor-kues', 'label' => 'Kuesioner SAQ', 'pattern' => 'penilai-eksternal/monitor-kues*,penilai-eksternal/review-kues*,evaluasi/saq*'],
                    'visitasi' => ['href' => '/penilai-eksternal/monitor-visitasi', 'label' => 'Visitasi & Verif. Faktual', 'pattern' => 'penilai-eksternal/monitor-visitasi*,penilai-eksternal/review-visitasi*,evaluasi/visitasi*'],
                    'wawancara' => ['href' => '/penilai-eksternal/monitor-wawancara', 'label' => 'Wawancara', 'pattern' => 'penilai-eksternal/monitor-wawancara*,penilai-eksternal/review-wawancara*,evaluasi/wawancara*'],
                ];

                $monLinks = [];
                foreach ($stageOrder as $stageKey) {
                    if (($stageKey === 'saq' || !empty($assigned[$stageKey])) && isset($stageLinks[$stageKey])) {
                        $monLinks[] = $stageLinks[$stageKey];
                    }
                }
                $monLinks[] = ['href' => '/penilai-eksternal/penilaian', 'label' => 'Penilaian Akhir', 'pattern' => 'penilai-eksternal/penilaian*,evaluasi/penilaian*'];

                return [
                    'role' => $role,
                    'sheets' => [
                        'monitoring' => [
                            'title' => 'Monitoring Kegiatan',
                            'links' => $monLinks,
                        ],
                    ],
                    'tabs' => [
                        [
                            'type' => 'link',
                            'label' => 'Beranda',
                            'href' => '/penilai-eksternal',
                            'icon' => 'home',
                            'active' => Request::is('penilai-eksternal', 'penilai-eksternal/', 'dashboard', 'dashboard/'),
                        ],
                        [
                            'type' => 'fab',
                            'label' => 'Monitoring',
                            'sheet' => 'monitoring',
                            'icon' => 'chart-bar',
                            'active' => Request::is(
                                'penilai-eksternal/monitor-kues*',
                                'penilai-eksternal/review-kues*',
                                'penilai-eksternal/monitor-visitasi*',
                                'penilai-eksternal/review-visitasi*',
                                'penilai-eksternal/monitor-wawancara*',
                                'penilai-eksternal/review-wawancara*',
                                'penilai-eksternal/penilaian*',
                                'evaluasi/*'
                            ),
                        ],
                        [
                            'type' => 'link',
                            'label' => 'Panduan',
                            'href' => $urlPanduan,
                            'icon' => 'book-open',
                            'target' => '_blank',
                            'active' => false,
                        ],
                        [
                            'type' => 'logout',
                            'label' => 'Keluar',
                            'icon' => 'logout',
                        ],
                    ],
                ];

            default:
                return [
                    'role' => $role,
                    'sheets' => [],
                    'tabs' => [
                        [
                            'type' => 'link',
                            'label' => 'Beranda',
                            'href' => '/' . $role,
                            'icon' => 'home',
                            'active' => Request::is($role, $role . '/*'),
                        ],
                        [
                            'type' => 'logout',
                            'label' => 'Keluar',
                            'icon' => 'logout',
                        ],
                    ],
                ];
        }
    }
}
