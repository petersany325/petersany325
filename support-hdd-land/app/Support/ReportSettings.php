<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ReportSettings
{
    public const SESSION_KEY = 'report_settings';

    public static function all(): array
    {
        $defaults = [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
            'period' => 'this_month',
            'show_charts' => true,
            'chart_type' => 'bar', // bar|line|doughnut
        ];

        $stored = session(self::SESSION_KEY, []);
        if (! is_array($stored)) {
            $stored = [];
        }

        return array_merge($defaults, $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function showCharts(): bool
    {
        return (bool) self::get('show_charts', true);
    }

    public static function chartType(): string
    {
        $type = (string) self::get('chart_type', 'bar');

        return in_array($type, ['bar', 'line', 'doughnut'], true) ? $type : 'bar';
    }

    /** @return array{0:string,1:string} */
    public static function range(): array
    {
        $all = self::all();

        return [(string) $all['from'], (string) $all['to']];
    }

    public static function applyRequest(Request $request): array
    {
        $current = self::all();

        if ($request->filled('period') && $request->get('period') !== 'custom') {
            [$from, $to] = self::resolvePeriod((string) $request->get('period'));
            $current['period'] = (string) $request->get('period');
            $current['from'] = $from;
            $current['to'] = $to;
        } else {
            if ($request->filled('from')) {
                $current['from'] = (string) $request->get('from');
                $current['period'] = 'custom';
            }
            if ($request->filled('to')) {
                $current['to'] = (string) $request->get('to');
                $current['period'] = 'custom';
            }
            if ($request->filled('period')) {
                $current['period'] = (string) $request->get('period');
            }
        }

        if ($request->has('show_charts')) {
            $current['show_charts'] = $request->boolean('show_charts');
        }
        if ($request->filled('chart_type')) {
            $type = (string) $request->get('chart_type');
            if (in_array($type, ['bar', 'line', 'doughnut'], true)) {
                $current['chart_type'] = $type;
            }
        }

        // Keep dates sane
        try {
            $from = Carbon::parse($current['from'])->startOfDay();
            $to = Carbon::parse($current['to'])->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            $current['from'] = $from->toDateString();
            $current['to'] = $to->toDateString();
        } catch (\Throwable) {
            $current['from'] = now()->startOfMonth()->toDateString();
            $current['to'] = now()->toDateString();
            $current['period'] = 'this_month';
        }

        session([self::SESSION_KEY => $current]);

        return $current;
    }

    /** Sync GET from/to into session without wiping chart prefs. */
    public static function syncFromQuery(Request $request): array
    {
        if ($request->filled('from') || $request->filled('to') || $request->filled('period')) {
            return self::applyRequest($request);
        }

        return self::all();
    }

    /** @return array{0:string,1:string} */
    public static function resolvePeriod(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => [$now->toDateString(), $now->toDateString()],
            'this_week' => [$now->copy()->startOfWeek()->toDateString(), $now->toDateString()],
            'last_30' => [$now->copy()->subDays(29)->toDateString(), $now->toDateString()],
            'this_year' => [$now->copy()->startOfYear()->toDateString(), $now->toDateString()],
            'last_month' => [
                $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            default => [$now->copy()->startOfMonth()->toDateString(), $now->toDateString()], // this_month
        };
    }

    public static function periodLabels(): array
    {
        return [
            'today' => 'امروز',
            'this_week' => 'این هفته',
            'this_month' => 'این ماه',
            'last_month' => 'ماه قبل',
            'last_30' => '۳۰ روز اخیر',
            'this_year' => 'امسال',
            'custom' => 'بازه دستی',
        ];
    }
}
