<?php

namespace App\Services;

use App\Jobs\SendBroadcastNotificationChunkJob;
use App\Models\AdminNotification;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BroadcastNotificationService
{
    public const CHUNK_SIZE = 200;

    public const USER_SEARCH_LIMIT = 20;

    /** Roles that cannot be selected as a broadcast audience. */
    public const EXCLUDED_ROLES = ['SuperAdmin'];

    /**
     * Active users (SoftDeletes excludes deleted/inactive ones):
     * everyone, users with one or more roles, or specific users.
     */
    public function recipientsQuery(string $audience, array $roles = [], array $userIds = []): Builder
    {
        return User::query()
            ->when($audience === AdminNotification::AUDIENCE_ROLES, function (Builder $query) use ($roles) {
                $query->whereHas('roles', fn (Builder $q) => $q->whereIn('name', $roles));
            })
            ->when($audience === AdminNotification::AUDIENCE_USERS, function (Builder $query) use ($userIds) {
                $query->whereIn('users.id', $userIds);
            });
    }

    public function countRecipients(string $audience, array $roles = [], array $userIds = []): int
    {
        return $this->recipientsQuery($audience, $roles, $userIds)->count();
    }

    /**
     * Search active users for the "Specifika användare" selector.
     * Every word must match name, last name or email ("anna sven" finds Anna Svensson).
     */
    public function searchUsers(?string $search, array $includeIds = []): Collection
    {
        $terms = preg_split('/\s+/u', trim((string) $search), -1, PREG_SPLIT_NO_EMPTY);

        $results = User::query()
            ->select(['id', 'name', 'last_name', 'email'])
            ->with('roles:id,name')
            ->when($terms, function (Builder $query) use ($terms) {
                foreach ($terms as $term) {
                    $like = '%' . addcslashes($term, '%_\\') . '%';
                    $query->where(fn (Builder $q) => $q
                        ->where('name', 'LIKE', $like)
                        ->orWhere('last_name', 'LIKE', $like)
                        ->orWhere('email', 'LIKE', $like));
                }
            })
            ->orderBy('name')
            ->orderBy('last_name')
            ->limit(self::USER_SEARCH_LIMIT)
            ->get();

        // Keep already selected users visible even if they do not match the current search.
        $missingIds = array_diff(array_map('intval', $includeIds), $results->pluck('id')->all());
        if ($missingIds) {
            $results = User::query()
                ->select(['id', 'name', 'last_name', 'email'])
                ->with('roles:id,name')
                ->whereIn('id', $missingIds)
                ->get()
                ->concat($results);
        }

        return $results->map(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'last_name' => $user->last_name,
            'full_name' => trim(($user->name ?? '') . ' ' . ($user->last_name ?? '')),
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->values(),
        ])->values();
    }

    public function availableRoles(): Collection
    {
        return Role::query()
            ->where('guard_name', 'api')
            ->whereNotIn('name', self::EXCLUDED_ROLES)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => $this->countRecipients(AdminNotification::AUDIENCE_ROLES, [$role->name]),
            ])
            ->values();
    }

    /**
     * Delete a broadcast together with the notifications it delivered to users
     * (removes them from every user's bell). Returns the number of user notifications deleted.
     */
    public function delete(AdminNotification $broadcast): int
    {
        return DB::transaction(function () use ($broadcast) {
            $deleted = Notification::query()->where('broadcast_id', $broadcast->id)->delete();
            $broadcast->delete();

            return $deleted;
        });
    }

    public function send(array $data, User $admin): AdminNotification
    {
        $audience = $data['audience'];
        $roles = $audience === AdminNotification::AUDIENCE_ROLES ? array_values($data['roles'] ?? []) : null;
        $userIds = $audience === AdminNotification::AUDIENCE_USERS
            ? array_values(array_unique(array_map('intval', $data['user_ids'] ?? [])))
            : null;

        return DB::transaction(function () use ($data, $admin, $audience, $roles, $userIds) {
            $broadcast = AdminNotification::create([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'],
                'route' => $data['route'] ?? null,
                'audience' => $audience,
                'roles' => $roles,
                'user_ids' => $userIds,
                'recipients_count' => $this->countRecipients($audience, $roles ?? [], $userIds ?? []),
                'state_id' => AdminNotification::stateId(AdminNotification::STATE_QUEUED),
                'user_id' => $admin->id,
            ]);

            if ($broadcast->recipients_count === 0) {
                $broadcast->update([
                    'state_id' => AdminNotification::stateId(AdminNotification::STATE_SENT),
                    'sent_at' => now(),
                ]);

                return $broadcast;
            }

            // Hold back one chunk so the last one can be flagged (isLastChunk) without an extra query.
            $pending = null;

            $this->recipientsQuery($audience, $roles ?? [], $userIds ?? [])
                ->select('users.id')
                ->chunkById(self::CHUNK_SIZE, function ($users) use (&$pending, $broadcast) {
                    if ($pending !== null) {
                        SendBroadcastNotificationChunkJob::dispatch($broadcast->id, $pending, false);
                    }

                    $pending = $users->pluck('id')->map(fn ($id) => (int) $id)->all();
                }, 'users.id', 'id');

            if ($pending !== null) {
                SendBroadcastNotificationChunkJob::dispatch($broadcast->id, $pending, true);
            } else {
                // Recipients disappeared between counting and chunking.
                $broadcast->update([
                    'recipients_count' => 0,
                    'state_id' => AdminNotification::stateId(AdminNotification::STATE_SENT),
                    'sent_at' => now(),
                ]);
            }

            return $broadcast;
        });
    }
}
