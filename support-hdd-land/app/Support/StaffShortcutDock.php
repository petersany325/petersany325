<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Personalizable icon shortcut dock (میانبر) for staff — especially technicians.
 */
class StaffShortcutDock
{
    public const MAX = 10;

    /**
     * Flat catalog of menu items the user may pin.
     *
     * @return list<array{id:string,label:string,route:string,mark:string,hint:string,group:string,tone:string}>
     */
    public static function catalog(User $user): array
    {
        $out = [];
        $seen = [];

        foreach (NavMenu::forUser($user) as $group) {
            $tone = NavMenu::tone((string) ($group['key'] ?? ''));
            $groupLabel = (string) ($group['label'] ?? '');

            $children = is_array($group['children'] ?? null) ? $group['children'] : [];
            if ($children === []) {
                $route = (string) ($group['route'] ?? '');
                if ($route !== '' && Route::has($route) && empty($seen[$route])) {
                    $seen[$route] = true;
                    $out[] = [
                        'id' => $route,
                        'label' => $groupLabel,
                        'route' => $route,
                        'mark' => (string) ($group['mark'] ?? '•'),
                        'hint' => (string) ($group['hint'] ?? ''),
                        'group' => $groupLabel,
                        'tone' => $tone,
                    ];
                }
                continue;
            }

            foreach ($children as $child) {
                $route = (string) ($child['route'] ?? '');
                if ($route === '' || ! Route::has($route) || ! empty($seen[$route])) {
                    continue;
                }
                $perm = $child['permission'] ?? null;
                if (is_string($perm) && $perm !== '' && ! self::childAllowed($user, $perm)) {
                    continue;
                }
                $seen[$route] = true;
                $out[] = [
                    'id' => $route,
                    'label' => (string) ($child['label'] ?? $route),
                    'route' => $route,
                    'mark' => (string) ($child['mark'] ?? ($group['mark'] ?? '•')),
                    'hint' => (string) ($child['hint'] ?? ''),
                    'group' => $groupLabel,
                    'tone' => $tone,
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array{id:string,label:string,route:string,mark:string,hint:string,group:string,tone:string,url:string,active:bool}>
     */
    public static function forUser(User $user): array
    {
        $catalog = collect(self::catalog($user))->keyBy('id');
        $ids = self::resolveIds($user, $catalog->keys()->all());

        $items = [];
        foreach ($ids as $id) {
            $row = $catalog->get($id);
            if (! $row) {
                continue;
            }
            $route = $row['route'];
            $items[] = array_merge($row, [
                'url' => route($route),
                'active' => request()->routeIs($route),
            ]);
            if (count($items) >= self::MAX) {
                break;
            }
        }

        return $items;
    }

    /**
     * @param  list<string>  $ids
     * @return list<string>
     */
    public static function sanitizeIds(User $user, array $ids): array
    {
        $allowed = collect(self::catalog($user))->pluck('id')->all();
        $allowedFlip = array_fill_keys($allowed, true);
        $clean = [];
        foreach ($ids as $id) {
            $id = trim((string) $id);
            if ($id === '' || empty($allowedFlip[$id]) || in_array($id, $clean, true)) {
                continue;
            }
            $clean[] = $id;
            if (count($clean) >= self::MAX) {
                break;
            }
        }

        return $clean;
    }

    /**
     * @return list<string>
     */
    public static function defaultsFor(User $user): array
    {
        $preferred = match ($user->role) {
            'technician' => [
                'handoffs.index',
                'receptions.search',
                'receptions.create',
                'parts.index',
                'parts.receipt',
                'work-reports.index',
                'daily-logs.index',
            ],
            'receptionist' => [
                'receptions.create',
                'receptions.search',
                'receptions.index',
                'deliveries.group',
                'customers.index',
                'parts.index',
            ],
            default => [
                'dashboard',
                'receptions.search',
                'handoffs.index',
                'parts.index',
                'daily-logs.index',
            ],
        };

        $allowed = array_fill_keys(collect(self::catalog($user))->pluck('id')->all(), true);
        $out = [];
        foreach ($preferred as $id) {
            if (! empty($allowed[$id])) {
                $out[] = $id;
            }
        }
        if ($out === []) {
            foreach (array_keys($allowed) as $id) {
                $out[] = $id;
                if (count($out) >= 5) {
                    break;
                }
            }
        }

        return array_slice($out, 0, self::MAX);
    }

    /**
     * @param  list<string>  $catalogIds
     * @return list<string>
     */
    private static function resolveIds(User $user, array $catalogIds): array
    {
        $saved = $user->ui_shortcuts;
        if (is_array($saved) && $saved !== []) {
            $allowed = array_fill_keys($catalogIds, true);
            $out = [];
            foreach ($saved as $id) {
                $id = trim((string) $id);
                if ($id !== '' && ! empty($allowed[$id]) && ! in_array($id, $out, true)) {
                    $out[] = $id;
                }
                if (count($out) >= self::MAX) {
                    break;
                }
            }
            if ($out !== []) {
                return $out;
            }
        }

        return self::defaultsFor($user);
    }

    private static function childAllowed(User $user, string $permissionExpr): bool
    {
        foreach (preg_split('/\|/', $permissionExpr) ?: [] as $p) {
            $p = trim($p);
            if ($p !== '' && $user->canAccess($p)) {
                return true;
            }
        }

        return false;
    }
}
