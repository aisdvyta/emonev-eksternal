<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Period extends Model
{
    protected $table = 'periods';
    protected $primaryKey = 'id_periode';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'periode_start' => 'date', 'periode_end' => 'date',
            'saq_start' => 'date', 'saq_end' => 'date',
            'eval_start' => 'date', 'eval_end' => 'date',
            'visit_start' => 'date', 'visit_end' => 'date',
            'interview_start' => 'date', 'interview_end' => 'date',
            'result_start' => 'date', 'result_end' => 'date',
        ];
    }

    /**
     * Periode yang sedang berjalan; jika tidak ada, periode terbaru.
     */
    public static function current(): ?self
    {
        $today = today()->toDateString();

        return static::query()
            ->whereDate('periode_start', '<=', $today)
            ->whereDate('periode_end', '>=', $today)
            ->orderByDesc('periode_start')
            ->first()
            ?? static::query()->orderByDesc('periode_start')->first();
    }

    /**
     * Nama tahapan yang sedang berlangsung hari ini.
     */
    public function phaseLabel(): string
    {
        $stages = [
            'Pengisian SAQ' => ['saq_start', 'saq_end'],
            'Evaluasi Kuesioner' => ['eval_start', 'eval_end'],
            'Visitasi' => ['visit_start', 'visit_end'],
            'Wawancara' => ['interview_start', 'interview_end'],
            'Pengumuman Hasil' => ['result_start', 'result_end'],
        ];

        $today = today();
        foreach ($stages as $label => [$start, $end]) {
            if ($this->$start && $this->$end && $today->between($this->$start->startOfDay(), $this->$end->endOfDay())) {
                return $label;
            }
        }

        return 'Di luar tahapan';
    }
}
