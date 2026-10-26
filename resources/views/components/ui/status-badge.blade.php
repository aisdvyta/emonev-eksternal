@props(['status'])

@php
    $variants = [
        'submitted' => [
            'label' => 'Submitted',
            'class' => 'bg-hijau2/10 text-hijau',
        ],
        'draft' => [
            'label' => 'Draft',
            'class' => 'bg-kuning2/20 text-kuning1 border-kuning2/40',
        ],
        'not_started' => [
            'label' => 'Not Started',
            'class' => 'bg-abubiru text-biru4 border-abubiru/35',
        ],
        'returned' => [
            'label' => 'Returned',
            'class' => 'bg-merah1/10 text-merah1 border-merah1/30',
        ],
        'verified' => [
            'label' => 'Verified',
            'class' => 'bg-biru5/20 text-biru2 border-biru5/40',
        ],
        'evaluated' => [
            'label' => 'Evaluated',
            'class' => 'bg-hijau2/10 text-hijau border-hijau2/30',
        ],
        'visitasi' => [
            'label' => 'Visitasi',
            'class' => 'bg-hijau2/10 text-hijau border-hijau2/30',
        ],
        'wawancara_done' => [
            'label' => 'Wawancara Done',
            'class' => 'bg-hijau2/10 text-hijau border-hijau2/30',
        ],
        'completed' => [
            'label' => 'Completed',
            'class' => 'bg-hijau text-white px-2 py-0.5 text-xs',
        ],
        'gugur' => [
            'label' => 'Gugur',
            'class' => 'bg-merah1 text-white px-2 py-0.5 text-xs',
        ],
        'informatif' => [
            'label' => 'Informatif',
            'class' => 'bg-hijau2/10 text-hijau border-hijau2/30',
        ],
        'in_progress' => [
            'label' => 'In Progress',
            'class' => 'bg-biru5/20 text-biru2 border-biru5/40',
        ],
        // Indonesian variants
        'belum_dimulai' => [
            'label' => 'Belum Dimulai',
            'class' => 'bg-abubiru text-biru4 border-abubiru/35',
        ],
        'draft_pengisian' => [
            'label' => 'Draft Pengisian',
            'class' => 'bg-kuning2/20 text-kuning1 border-kuning2/40',
        ],
        'menunggu_verif' => [
            'label' => 'Menunggu Verif',
            'class' => 'bg-oren2/10 text-oren2 border-oren2/30',
        ],
        'draft_verif' => [
            'label' => 'Draft Verif',
            'class' => 'bg-birumuda text-biru6 border-biru5/40',
        ],
        'dikembalikan' => [
            'label' => 'Dikembalikan',
            'class' => 'bg-merah1/10 text-merah1 border-merah1/30',
        ],
        'menunggu_eval' => [
            'label' => 'Menunggu Eval',
            'class' => 'bg-biru5/20 text-biru2 border-biru5/40',
        ],
        'draft_penilaian' => [
            'label' => 'Draft Penilaian',
            'class' => 'bg-biru5/30 text-biru2 border-biru5/50',
        ],
        'draft_evaluasi' => [
            'label' => 'Draft Evaluasi',
            'class' => 'bg-biru5/30 text-biru2 border-biru5/50',
        ],
        'lolos' => [
            'label' => 'Lolos',
            'class' => 'bg-hijau/10 text-hijau border-hijau/30',
        ],
        'selesai' => [
            'label' => 'Lolos',
            'class' => 'bg-hijau/10 text-hijau border-hijau/30',
        ],
        'proses' => [
            'label' => 'Proses',
            'class' => 'bg-biru5/20 text-biru2 border-biru5/40',
        ],
        // KIP Monev Predikat Variants
        'informatif' => [
            'label' => 'Informatif',
            'class' => 'bg-hijau/10 text-hijau',
        ],
        'menuju informatif' => [
            'label' => 'Menuju Informatif',
            'class' => 'bg-hijaumuda/10 text-hijaumuda',
        ],
        'menuju_informatif' => [
            'label' => 'Menuju Informatif',
            'class' => 'bg-hijaumuda/10 text-hijaumuda',
        ],
        'cukup informatif' => [
            'label' => 'Cukup Informatif',
            'class' => 'bg-kuning2/40 text-oren2',
        ],
        'cukup_informatif' => [
            'label' => 'Cukup Informatif',
            'class' => 'bg-kuning2/40 text-oren2',
        ],
        'kurang informatif' => [
            'label' => 'Kurang Informatif',
            'class' => 'bg-oren2/10 text-oren2',
        ],
        'kurang_informatif' => [
            'label' => 'Kurang Informatif',
            'class' => 'bg-oren2/10 text-oren2',
        ],
        'tidak informatif' => [
            'label' => 'Tidak Informatif',
            'class' => 'bg-merah2/10 text-merah2',
        ],
        'tidak_informatif' => [
            'label' => 'Tidak Informatif',
            'class' => 'bg-merah2/10 text-merah2',
        ],
        'belum dinilai' => [
            'label' => 'Belum Dinilai',
            'class' => 'bg-slate-100 text-slate-500',
        ],
        'belum_dinilai' => [
            'label' => 'Belum Dinilai',
            'class' => 'bg-slate-100 text-slate-500',
        ],
    ];

    $variant = $variants[strtolower($status)] ?? [
        'label' => $status,
        'class' => 'bg-abubiru text-biru3',
    ];
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-flex rounded-full px-3 py-1.5 text-xs font-bold ' . $variant['class']]) }}>
    {{ $variant['label'] }}
</span>
