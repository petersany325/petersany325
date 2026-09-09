<?php

namespace App\Services;

use App\Models\StaffNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class StaffNotifier
{
    /**
     * @param  iterable<int, User|int>  $users
     * @param  array<string, mixed>  $data
     */
    public function notifyMany(iterable $users, string $type, string $title, ?string $body = null, ?string $link = null, array $data = []): void
    {
        $ids = collect($users)->map(function ($u) {
            return $u instanceof User ? $u->id : (int) $u;
        })->filter()->unique()->values();

        foreach ($ids as $userId) {
            StaffNotification::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $link,
                'data' => $data ?: null,
            ]);
            Cache::forget('unread_notes_'.$userId);
        }
    }

    /** @return Collection<int, User> */
    public function deskUsers(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('role', 'admin')
                    ->orWhere('role', 'receptionist')
                    ->orWhereJsonContains('permissions', 'receptions');
            })
            ->get();
    }

    /** @return Collection<int, User> */
    public function messageRecipients(): Collection
    {
        return User::query()
            ->where('is_active', true)
            ->whereIn('role', ['admin', 'receptionist', 'technician', 'employee'])
            ->get();
    }
}
