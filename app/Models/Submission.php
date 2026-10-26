<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Submission extends Model
{
    protected $table = 'submissions';
    protected $primaryKey = 'id_submission';
    public $timestamps = false;

    protected static $userCache = [];

    public static function preloadUsers($submissions)
    {
        if (empty($submissions)) {
            return;
        }

        $userIds = [];
        foreach ($submissions as $sub) {
            if (!$sub) continue;

            // Collect operator IDs
            $opRaw = $sub->id_operator;
            $opEntries = is_array($opRaw) ? $opRaw : [];
            foreach ($opEntries as $entry) {
                if (is_array($entry) && isset($entry['id'])) {
                    $userIds[] = $entry['id'];
                } elseif (!is_array($entry) && $entry) {
                    $userIds[] = $entry;
                }
            }

            // Collect evaluator IDs
            if ($sub->relationLoaded('evaluators')) {
                foreach ($sub->evaluators as $evUser) {
                    if ($evUser && $evUser->id_user) {
                        $userIds[] = $evUser->id_user;
                    }
                }
            } elseif (isset($sub->attributes['evaluated_by']) && !empty($sub->attributes['evaluated_by'])) {
                $evalRaw = $sub->attributes['evaluated_by'];
                $evalEntries = is_array($evalRaw) ? $evalRaw : (json_decode($evalRaw, true) ?: []);
                if (!is_array($evalEntries)) {
                    $evalEntries = [$evalEntries];
                }
                foreach ($evalEntries as $entry) {
                    if (is_array($entry) && isset($entry['id'])) {
                        $userIds[] = $entry['id'];
                    } elseif (!is_array($entry) && $entry) {
                        $userIds[] = $entry;
                    }
                }
            }

            // Collect verified_by ID
            if ($sub->verified_by) {
                $userIds[] = $sub->verified_by;
            }

            // Collect visitasi evaluator user IDs
            if ($sub->relationLoaded('visitasi') && $sub->visitasi) {
                if ($sub->visitasi->relationLoaded('evaluators')) {
                    foreach ($sub->visitasi->evaluators as $vEv) {
                        if ($vEv && $vEv->id_user) {
                            $userIds[] = $vEv->id_user;
                        }
                    }
                } elseif (!empty($sub->visitasi->visit_data)) {
                    $vData = is_array($sub->visitasi->visit_data) ? $sub->visitasi->visit_data : json_decode($sub->visitasi->visit_data, true);
                    foreach ($vData ?? [] as $vItem) {
                        if (!empty($vItem['id_user'])) {
                            $userIds[] = $vItem['id_user'];
                        }
                    }
                }
            }

            // Collect interview evaluator user IDs
            if ($sub->relationLoaded('interview') && $sub->interview) {
                if ($sub->interview->relationLoaded('evaluators')) {
                    foreach ($sub->interview->evaluators as $iEv) {
                        if ($iEv && $iEv->id_user) {
                            $userIds[] = $iEv->id_user;
                        }
                    }
                } elseif (!empty($sub->interview->interview_data)) {
                    $iData = is_array($sub->interview->interview_data) ? $sub->interview->interview_data : json_decode($sub->interview->interview_data, true);
                    foreach ($iData ?? [] as $iItem) {
                        if (!empty($iItem['id_user'])) {
                            $userIds[] = $iItem['id_user'];
                        }
                    }
                }
            }
        }

        $userIds = array_values(array_unique(array_filter($userIds)));
        \App\Services\UserCacheService::preload($userIds);
    }

    public static function findUserWithCache($id)
    {
        return \App\Services\UserCacheService::get($id);
    }

    /**
     * Factory method untuk membuat instance Submission mock/dummy untuk satker yang belum memiliki submission.
     */
    public static function makeDummy(MasterSatker $satker, ?Period $period = null, ?int $periodId = null): self
    {
        $sub = new self();
        $sub->id_submission    = null;
        $sub->status           = 0;
        $sub->calculated_final = 0.0;
        $sub->predikat         = 'BELUM DINILAI';
        $sub->is_final         = false;

        $resultMock = new Result();
        $resultMock->kode_satker = $satker->kode_satker;
        $resultMock->id_periode  = $period?->id_periode ?? $periodId;
        $resultMock->setRelation('satker', $satker);
        $resultMock->setRelation('period', $period);

        $sub->setRelation('result', $resultMock);
        $sub->setRelation('visitasi', null);
        $sub->setRelation('interview', null);

        return $sub;
    }

    /**
     * Resolve a list of evaluator name fragments (lowercase) into matching user IDs.
     * Centralizes the User lookup so all Monitor tables share one implementation.
     *
     * @param  string[]  $names  Array of lowercase name fragments (from filterEvaluator)
     * @return int[]
     */
    public static function userIdsFromEvaluatorNames(array $names): array
    {
        $cleanedNames = array_values(array_unique(array_filter(array_map('trim', $names))));
        if (empty($cleanedNames)) {
            return [];
        }

        return User::where(function ($q) use ($cleanedNames) {
            foreach ($cleanedNames as $name) {
                $q->orWhere('nama_user', 'like', '%' . $name . '%');
            }
        })->pluck('id_user')->toArray();
    }

    protected $fillable = [
        'id_result',
        'id_operator',
        'verified_by',
        'verified_at',
        'verification_note',
        'status',
        'draft_saved_at',
        'interview_status',
        'submitted_at',
        'evaluated_at',
    ];

    protected $casts = [
        'id_operator' => 'array',
        'status' => 'integer',
        'interview_status' => 'integer',
    ];

    public function getEvaluatedByAttribute()
    {
        if (isset($this->attributes['evaluated_by']) && !is_null($this->attributes['evaluated_by'])) {
            return is_string($this->attributes['evaluated_by'])
                ? json_decode($this->attributes['evaluated_by'], true)
                : $this->attributes['evaluated_by'];
        }

        if ($this->relationLoaded('evaluators')) {
            return $this->evaluators->pluck('id_user')->toArray();
        }

        return $this->evaluators()->pluck('users.id_user')->toArray();
    }

    /**
     * Helper: normalize id_operator entries to {id, nama} objects.
     * Supports both old format [123] and new format [{id:123, nama:"..."}]
     */
    private function normalizeOperatorEntries(): array
    {
        $raw = $this->id_operator;
        if (empty($raw)) {
            return [];
        }
        $entries = is_array($raw) ? $raw : [];
        return array_map(function ($entry) {
            if (is_array($entry) && isset($entry['id'])) {
                // New format: {id, nama}
                return $entry;
            }
            // Old format: plain integer ID — resolve name from DB if possible
            $user = self::findUserWithCache($entry);
            return [
                'id'   => $entry,
                'nama' => $user?->nama_user ?? 'Operator (Dihapus)',
            ];
        }, $entries);
    }

    /**
     * Backward compatibility: Get the first operator as "user"
     */
    public function getUserAttribute()
    {
        $entries = $this->normalizeOperatorEntries();
        if (empty($entries)) {
            $dummyUser = new User();
            $dummyUser->nama_user = 'Dummy Operator';
            if ($this->result && $this->result->satker) {
                $dummyUser->kode_satker = $this->result->kode_satker;
                $dummyUser->setRelation('masterSatker', $this->result->satker);
            } else {
                $dummyUser->setRelation('masterSatker', new MasterSatker());
            }
            return $dummyUser;
        }

        $first = $entries[0];
        $user = self::findUserWithCache($first['id']);
        if (!$user) {
            // User deleted — return dummy with snapshot name
            $dummyUser = new User();
            $dummyUser->id_user = $first['id'];
            $dummyUser->nama_user = $first['nama'] ?? 'Operator (Dihapus)';
            if ($this->result && $this->result->satker) {
                $dummyUser->kode_satker = $this->result->kode_satker;
                $dummyUser->setRelation('masterSatker', $this->result->satker);
            } else {
                $dummyUser->setRelation('masterSatker', new MasterSatker());
            }
            return $dummyUser;
        }
        if (!$user->masterSatker && $this->result && $this->result->satker) {
            $user->setRelation('masterSatker', $this->result->satker);
        }
        return $user;
    }

    public function getOperatorsAttribute()
    {
        $entries = $this->normalizeOperatorEntries();
        if (empty($entries)) {
            return collect();
        }

        return collect($entries)->map(function ($entry) {
            $user = self::findUserWithCache($entry['id']);
            if ($user) {
                return $user;
            }
            // User deleted — return dummy with snapshot name
            $dummy = new User();
            $dummy->id_user  = $entry['id'];
            $dummy->nama_user = $entry['nama'] ?? 'Operator (Dihapus)';
            return $dummy;
        });
    }

    public function getPeriodAttribute()
    {
        return $this->result ? $this->result->period : null;
    }

    public function getIdPeriodeAttribute()
    {
        return $this->result ? $this->result->id_periode : null;
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by', 'id_user');
    }

    public function result()
    {
        return $this->belongsTo(Result::class, 'id_result', 'id_result');
    }

    public function visitasi()
    {
        return $this->hasOne(Visitasi::class, 'id_result', 'id_result');
    }
 
    public function interview()
    {
        return $this->hasOne(Interview::class, 'id_result', 'id_result');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'id_submission', 'id_submission');
    }

    /**
     * Helper: normalize evaluated_by entries to {id, nama} objects.
     * Supports both old format [123] and new format [{id:123, nama:"..."}]
     */
    private function normalizeEvaluatorEntries(): array
    {
        $raw = $this->attributes['evaluated_by'] ?? null;
        if (empty($raw)) {
            return [];
        }
        $entries = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        if (!is_array($entries)) {
            $entries = [$entries];
        }
        return array_map(function ($entry) {
            if (is_array($entry) && isset($entry['id'])) {
                // New format: {id, nama}
                return $entry;
            }
            // Old format: plain integer ID — resolve name from DB if possible
            $user = self::findUserWithCache($entry);
            return [
                'id'   => $entry,
                'nama' => $user?->nama_user ?? 'Evaluator (Dihapus)',
            ];
        }, $entries);
    }

    public function getKuesionerEvaluatorsAttribute()
    {
        if ($this->relationLoaded('evaluators') && $this->evaluators->isNotEmpty()) {
            return $this->evaluators;
        }

        $entries = $this->normalizeEvaluatorEntries();
        if (empty($entries)) {
            return collect();
        }

        return collect($entries)->map(function ($entry) {
            $user = self::findUserWithCache($entry['id']);
            if ($user) {
                return $user;
            }
            // User deleted — return dummy with snapshot name
            $dummy = new User();
            $dummy->id_user   = $entry['id'];
            $dummy->nama_user = $entry['nama'] ?? 'Evaluator (Dihapus)';
            return $dummy;
        });
    }

    public function evaluators()
    {
        return $this->belongsToMany(User::class, 'submission_evaluators', 'id_submission', 'id_user');
    }

    public function submissionEvaluators()
    {
        return $this->hasMany(SubmissionEvaluator::class, 'id_submission', 'id_submission');
    }

    /**
     * Alias for kuesionerEvaluators for backward compatibility
     */
    public function getEvaluatorsAttribute()
    {
        if ($this->relationLoaded('evaluators')) {
            return $this->getRelation('evaluators');
        }
        $rel = $this->evaluators()->get();
        if ($rel->isNotEmpty()) {
            return $rel;
        }
        return $this->getKuesionerEvaluatorsAttribute();
    }

    public static function statusLabels(): array
    {
        return [
            0 => 'Draft Pengisian',
            1 => 'Menunggu Verifikasi',
            2 => 'Dikembalikan',
            3 => 'Menunggu Evaluasi',
            4 => 'Draft Evaluasi',
            5 => 'Lolos',
            6 => 'Wawancara Selesai',
            7 => 'Hasil Diterbitkan',
            8 => 'Gugur',
        ];
    }

    public static function statusColorClasses(): array
    {
        return [
            0 => 'bg-kuning1 text-white',
            1 => 'bg-oren2/75 text-white',
            2 => 'bg-merah1 text-white',
            3 => 'bg-biru2 text-white',
            4 => 'bg-biru4 text-white',
            5 => 'bg-hijau/80 text-white',
            6 => 'bg-hijau/80 text-white',
            7 => 'bg-hijau/80 text-white',
            8 => 'bg-merah1 text-white',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? 'Unknown';
    }

    public function getStatusColorClassAttribute(): string
    {
        return self::statusColorClasses()[$this->status] ?? 'bg-biru3 text-white';
    }

    public function skSertifikat()
    {
        return $this->hasOne(SkSertifikat::class, 'id_submission', 'id_submission');
    }
}
