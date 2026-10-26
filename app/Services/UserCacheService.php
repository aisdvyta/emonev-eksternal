<?php

namespace App\Services;

use App\Models\User;

class UserCacheService
{
    /** @var array<int, User> Static request-level cache */
    protected static array $cache = [];

    /**
     * Dapatkan user berdasarkan ID dari in-memory cache, atau fetch jika belum ada.
     */
    public static function get(int|string|null $id): ?User
    {
        if (!$id) {
            return null;
        }

        $intId = (int) $id;
        if (!isset(self::$cache[$intId])) {
            $user = User::with(['roles', 'masterSatker'])->find($intId);
            if ($user) {
                self::$cache[$intId] = $user;
            }
        }

        return self::$cache[$intId] ?? null;
    }

    /**
     * Preload sekumpulan user IDs ke dalam cache dalam 1 query.
     */
    public static function preload(array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return;
        }

        $missing = array_diff($userIds, array_keys(self::$cache));
        if (!empty($missing)) {
            $users = User::with(['roles', 'masterSatker'])->whereIn('id_user', $missing)->get();
            foreach ($users as $user) {
                self::$cache[$user->id_user] = $user;
            }
        }
    }

    /**
     * Set user instance ke cache secara manual.
     */
    public static function set(User $user): void
    {
        if ($user->id_user) {
            self::$cache[$user->id_user] = $user;
        }
    }

    /**
     * Bersihkan cache (berguna saat test).
     */
    public static function clear(): void
    {
        self::$cache = [];
    }
}
