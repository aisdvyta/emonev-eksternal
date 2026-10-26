<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Visitasi extends Model
{
    use HasUuids;

    /** @var array<int, User> Static in-request cache agar getEvaluatorsAttribute() tidak N+1 */
    protected static array $userWithRolesCache = [];

    protected $table = 'visitasi';
    protected $primaryKey = 'id_visit';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'id_result',
        'visit_mode',
        'link_meet_visitasi',
        'link_meeting',
        'visit_at',
        'visit_score',
        'visit_note',
        'link_buktidukung',
     ];

    public function getLinkMeetingAttribute()
    {
        return $this->attributes['link_meet_visitasi'] ?? null;
    }

    public function setLinkMeetingAttribute($value)
    {
        $this->attributes['link_meet_visitasi'] = $value;
    }

    protected $casts = [];

    public function getVisitDataAttribute()
    {
        if (isset($this->attributes['visit_data']) && !is_null($this->attributes['visit_data'])) {
            return is_string($this->attributes['visit_data'])
                ? json_decode($this->attributes['visit_data'], true)
                : $this->attributes['visit_data'];
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
        return $this->hasMany(VisitasiEvaluator::class, 'id_visit', 'id_visit');
    }

    public function evaluatorUsers()
    {
        return $this->belongsToMany(User::class, 'visitasi_evaluators', 'id_visit', 'id_user');
    }

    public function getIdVisitorAttribute()
    {
        $data = $this->visit_data ?? [];
        $ids = collect($data)->pluck('id_user')->toArray();
        return implode(',', $ids);
    }

    /**
     * Preload semua evaluator users (dengan roles) dari kumpulan visitasi sekaligus.
     * Panggil ini setelah load collection visitasi agar getEvaluatorsAttribute() pakai cache.
     */
    public static function preloadEvaluatorUsers(iterable $visitasiCollection): void
    {
        $userIds = [];
        foreach ($visitasiCollection as $v) {
            if (!$v) continue;
            if ($v->relationLoaded('evaluators')) {
                foreach ($v->getRelation('evaluators') as $ev) {
                    if (!empty($ev->id_user)) {
                        $userIds[] = (int) $ev->id_user;
                    }
                }
            } elseif (isset($v->attributes['visit_data']) && !empty($v->attributes['visit_data'])) {
                $raw = is_string($v->attributes['visit_data']) ? json_decode($v->attributes['visit_data'], true) : $v->attributes['visit_data'];
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

    public function getEvaluatorsAttribute()
    {
        $evs = $this->relationLoaded('evaluators')
            ? $this->getRelation('evaluators')
            : $this->evaluators()->with('user.roles')->get();

        if ($evs->isNotEmpty()) {
            $rolePriority = function ($ev) {
                $user = $ev->user ?? null;
                if (!$user) return 99;

                if ($ev->is_external || (method_exists($user, 'hasRole') && $user->hasRole('penilai_eksternal'))) {
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

            return $evs->sortBy([
                fn($a, $b) => $rolePriority($a) <=> $rolePriority($b),
                fn($a, $b) => strcmp(strtolower($a->user->name ?? ''), strtolower($b->user->name ?? '')),
            ])->values();
        }

        // Fallback jika masih ada data legacy visit_data
        $data = isset($this->attributes['visit_data']) && !is_null($this->attributes['visit_data'])
            ? (is_string($this->attributes['visit_data']) ? json_decode($this->attributes['visit_data'], true) : $this->attributes['visit_data'])
            : [];

        $evaluatorsList = [];
        foreach ($data as $item) {
            $userId = $item['id_user'] ?? null;
            if (!$userId) continue;

            $user = \App\Services\UserCacheService::get($userId);
            if ($user) {
                $evaluatorsList[] = (object) [
                    'id_user' => $item['id_user'],
                    'user'    => $user,
                    'score'   => $item['score'] ?? null,
                    'note'    => $item['note'] ?? null,
                    'is_draft' => $item['is_draft'] ?? true,
                ];
            }
        }

        return collect($evaluatorsList);
    }
}
