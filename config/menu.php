<?php

return [
    /*
    |--------------------------------------------------------------------------
    | HaloKIP Unified Navigation Configuration
    |--------------------------------------------------------------------------
    |
    | Blueprint konfigurasi navigasi terpusat untuk arsitektur Feature/Domain-Driven.
    | Digunakan sebagai acuan role-based access control pada menu aplikasi.
    |
    */

    'items' => [
        [
            'label' => 'Beranda',
            'icon'  => 'home',
            'route' => 'dashboard',
            'roles' => ['*'],
            'bottom_nav' => true,
        ],
        [
            'label' => 'Manajemen User',
            'icon'  => 'users',
            'roles' => ['admin', 'super_evaluator'],
            'bottom_nav' => true,
        ],
        [
            'label' => 'Manajemen Jadwal',
            'icon'  => 'calendar',
            'roles' => ['super_evaluator'],
            'bottom_nav' => true,
        ],
        [
            'label' => 'Master Data',
            'icon'  => 'database',
            'roles' => ['evaluator', 'super_evaluator'],
            'bottom_nav' => true,
            'children' => [
                ['label' => 'Master Pembobotan', 'roles' => ['evaluator', 'super_evaluator']],
                ['label' => 'Master Indikator', 'roles' => ['evaluator', 'super_evaluator']],
                ['label' => 'Master Sub-Indikator', 'roles' => ['evaluator', 'super_evaluator']],
                ['label' => 'Master Pertanyaan', 'roles' => ['evaluator', 'super_evaluator']],
            ],
        ],
        [
            'label' => 'Kegiatan Evaluasi',
            'icon'  => 'clipboard-list',
            'roles' => ['operator', 'evaluator', 'super_evaluator', 'verifikator', 'penilai_eksternal'],
            'bottom_nav' => true,
            'children' => [
                [
                    'label'   => 'Kuesioner SAQ',
                    'stage'   => 'saq',
                    'roles'   => ['operator', 'evaluator', 'super_evaluator', 'verifikator'],
                ],
                [
                    'label'   => 'Visitasi & Verif. Faktual',
                    'stage'   => 'visitasi',
                    'roles'   => ['operator', 'evaluator', 'super_evaluator', 'verifikator'],
                ],
                [
                    'label'   => 'Wawancara',
                    'stage'   => 'wawancara',
                    'roles'   => ['operator', 'evaluator', 'super_evaluator', 'penilai_eksternal'],
                ],
                [
                    'label'   => 'Penilaian Akhir',
                    'roles'   => ['*'],
                ],
            ],
        ],
        [
            'label' => 'SK & Sertifikat',
            'icon'  => 'document',
            'roles' => ['operator', 'evaluator', 'super_evaluator', 'verifikator'],
            'bottom_nav' => true,
        ],
        [
            'label' => 'Panduan',
            'icon'  => 'book-open',
            'roles' => ['*'],
            'external' => true,
        ],
    ],
];
