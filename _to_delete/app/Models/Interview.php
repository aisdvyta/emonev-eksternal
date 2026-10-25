<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    protected $table = 'interview';
    protected $primaryKey = 'id_interview';
    public $timestamps = false;

    /** @var array<int, User> Static in-request cache: users+roles untuk semua interviewers */
    protected static array $userWithRolesCache = [];

    /** Cached result dari getInterviewersAttribute() per instance */
    protected ?object $_cachedInterviewers = null;

    protected $fillable = [
        'id_result',
        'link_meet_interview',
        'meets_link',
        'interview_at',
        'interview_score',
    ];

    public function getMeetsLinkAttribute()
    {
        return $this->attributes['link_meet_interview'] ?? null;
    }

    public function setMeetsLinkAttribute($value)
    {
        $this->attributes['link_meet_interview'] = $value;
    }

    protected $casts = [];

    public function getInterviewDataAttribute()
    {
        if (isset($this->attributes['interview_data']) && !is_null($this->attributes['interview_data'])) {
            return is_string($this->attributes['interview_data'])
                ? json_decode($this->attributes['interview_data'], true)
                : $this->attributes['interview_data'];
        }

        $evs = $this->relationLoaded('evaluators')
            ? $this->getRelation('evaluators')
            : $this->evaluators()->with('user')->get();

        if ($evs->isNotEmpty()) {
            return $evs->map(function ($ev) {
                return [
                    'id_user'     => $ev->id_user,
                    'score'       => $ev->score,
                    'catatan'     => $ev->catatan,
                    'note'        => $ev->catatan,
                    'is_draft'    => (bool) $ev->is_draft,
                    'is_external' => (bool) $ev->is_external,
                    'name'        => $ev->name,
                    'instansi'    => $ev->instansi,
                ];
            })->toArray();
        }

        return [];
    }

    public function result()
    {
        return $this->belongsTo(Result::class, 'id_result', 'id_result');
    }

    public function getPeriodAttribute()
    {
        return $this->result ? $this->result->period : null;
    }

    public function getSubmissionAttribute()
    {
        return $this->result ? $this->result->submission : null;
    }

    public function evaluators()
    {
        return $this->hasMany(InterviewEvaluator::class, 'id_interview', 'id_interview');
    }

    public function evaluatorUsers()
    {
        return $this->belongsToMany(User::class, 'interview_evaluators', 'id_interview', 'id_user');
    }

    /**
     * Preload semua interviewer users (dengan roles) dari kumpulan interview sekaligus.
     * Panggil ini setelah load collection interview agar getInterviewersAttribute() pakai cache.
     */
    public static function preloadInterviewerUsers(iterable $interviewCollection): void
    {
        $userIds = [];
        foreach ($interviewCollection as $interview) {
            if (!$interview) continue;
            if ($interview->relationLoaded('evaluators')) {
                foreach ($interview->getRelation('evaluators') as $ev) {
                    if (!empty($ev->id_user)) {
                        $userIds[] = (int) $ev->id_user;
                    }
                }
            } elseif (isset($interview->attributes['interview_data']) && !empty($interview->attributes['interview_data'])) {
                $raw = is_string($interview->attributes['interview_data']) ? json_decode($interview->attributes['interview_data'], true) : $interview->attributes['interview_data'];
                foreach ($raw ?? [] as $item) {
                    if (!empty($item['id_user'])) {
                        $userIds[] = (int) $item['id_user'];
                    }
                }
            }
        }
        $userIds = array_values(array_unique(array_filter($userIds)));
        if (!empty($userIds)) {
            \App\Services\UserCacheService::preload($userIds);
        }
    }

    public function getInterviewersAttribute()
    {
        // Instance-level cache: cegah re-query dari compat accessors (interviewer1, score1, dll)
        if ($this->_cachedInterviewers !== null) {
            return $this->_cachedInterviewers;
        }

        $data = $this->interview_data ?? [];
        $interviewersList = [];

        foreach ($data as $item) {
            if (isset($item['is_external']) && $item['is_external']) {
                $userObj = null;
                $userId = $item['id_user'] ?? null;
                if ($userId) {
                    $userObj = \App\Services\UserCacheService::get($userId);
                }

                $interviewersList[] = (object) [
                    'id_user'     => $userId,
                    'user'        => $userObj ?? (object) [
                        'name' => $item['name'] ?? 'Penilai Eksternal',
                        'role' => (object) [
                            'nama_role' => !empty($item['instansi']) ? $item['instansi'] : 'Penilai Eksternal'
                        ]
                    ],
                    'score'       => $item['score'] ?? null,
                    'note'        => $item['note'] ?? null,
                    'is_draft'    => $item['is_draft'] ?? false,
                    'is_external' => true,
                    'instansi'    => $item['instansi'] ?? ''
                ];
                continue;
            }

            $userId = $item['id_user'] ?? null;
            if (!$userId) continue;

            $user = \App\Services\UserCacheService::get($userId);
            if ($user) {
                $interviewersList[] = (object) [
                    'id_user'     => $item['id_user'],
                    'user'        => $user,
                    'score'       => $item['score'] ?? null,
                    'note'        => $item['note'] ?? null,
                    'is_draft'    => $item['is_draft'] ?? true,
                    'is_external' => $user->hasRole('penilai_eksternal'),
                ];
            }
        }

        $rolePriority = function ($ev) {
            if (isset($ev->is_external) && $ev->is_external) {
                return 3;
            }
            $user = $ev->user ?? null;
            if (!$user) return 99;

            if (method_exists($user, 'hasRole') && $user->hasRole('penilai_eksternal')) {
                return 3;
            }

            $roleName = strtolower($user->role->nama_role ?? '');
            if ($roleName === 'evaluator' || (method_exists($user, 'hasRole') && $user->hasRole('evaluator'))) {
                return 1;
            }
            if ($roleName === 'super_evaluator' || $roleName === 'superevaluator' || (method_exists($user, 'hasRole') && $user->hasRole('super_evaluator'))) {
                return 2;
            }
            if ($roleName === 'penilai_eksternal' || str_contains($roleName, 'eksternal')) {
                return 3;
            }

            return 4;
        };

        $sorted = collect($interviewersList)->sortBy([
            fn($a, $b) => $rolePriority($a) <=> $rolePriority($b),
            fn($a, $b) => strcmp(strtolower($a->user->name ?? ($a->name ?? '')), strtolower($b->user->name ?? ($b->name ?? ''))),
        ])->values();

        $this->_cachedInterviewers = $sorted;
        return $this->_cachedInterviewers;
    }

    public function getInternalInterviewersAttribute()
    {
        return $this->getInterviewersAttribute()->where('is_external', false)->values();
    }

    public function getExternalInterviewersAttribute()
    {
        return $this->getInterviewersAttribute()->where('is_external', true)->values();
    }

    public function getExternalInterviewerAttribute()
    {
        return $this->getExternalInterviewersAttribute()->first();
    }

    public function getExternalInterviewerIdAttribute()
    {
        $ext = $this->getExternalInterviewerAttribute();
        return $ext?->id_user ? (string) $ext->id_user : '';
    }

    public function getExternalInterviewerIdsAttribute()
    {
        return $this->getExternalInterviewersAttribute()
            ->pluck('id_user')
            ->filter()
            ->map(fn($id) => (string) $id)
            ->values()
            ->toArray();
    }

    // Compatibility accessors for old code expecting interviewer 1/2/3 (Internal BPS)
    public function getInterviewer1Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(0)?->user ?? null;
    }

    public function getInterviewer2Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(1)?->user ?? null;
    }

    public function getInterviewer3Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(2)?->user ?? null;
    }

    public function getScoreInterviewer1Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(0)?->score ?? null;
    }

    public function getScoreInterviewer2Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(1)?->score ?? null;
    }

    public function getScoreInterviewer3Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(2)?->score ?? null;
    }

    public function getNoteInterviewer1Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(0)?->note ?? null;
    }

    public function getNoteInterviewer2Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(1)?->note ?? null;
    }

    public function getNoteInterviewer3Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(2)?->note ?? null;
    }

    public function getIsDraft1Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(0)?->is_draft ?? true;
    }

    public function getIsDraft2Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(1)?->is_draft ?? true;
    }

    public function getIsDraft3Attribute()
    {
        return $this->getInternalInterviewersAttribute()->get(2)?->is_draft ?? true;
    }

    public function getInterviewer1IdAttribute()
    {
        return $this->getInternalInterviewersAttribute()->get(0)?->id_user ?? null;
    }

    public function getInterviewer2IdAttribute()
    {
        return $this->getInternalInterviewersAttribute()->get(1)?->id_user ?? null;
    }

    public function getInterviewer3IdAttribute()
    {
        return $this->getInternalInterviewersAttribute()->get(2)?->id_user ?? null;
    }
}
