<?php

namespace App\Support;

use App\Models\AppSetting;
use App\Models\LookupOption;
use App\Models\Reception;

class CostApprovalSettings
{
    public const SETTING_KEY = 'cost_approval_services';

    /** Default service/repair names that require customer cost approval */
    public const DEFAULTS = [
        'جراحی هارد',
        'جراحی',
        'بازیابی اطلاعات',
        'دیتا ریکاوری',
    ];

    /**
     * @return list<string>
     */
    public static function enabledServices(): array
    {
        $raw = AppSetting::getValue(self::SETTING_KEY);
        if ($raw === null || $raw === '') {
            return self::DEFAULTS;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded)) {
            return self::DEFAULTS;
        }

        return array_values(array_unique(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $decoded
        ))));
    }

    /**
     * @param  list<string>  $names
     */
    public static function setEnabledServices(array $names): void
    {
        $clean = array_values(array_unique(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $names
        ))));

        AppSetting::setValue(self::SETTING_KEY, json_encode($clean, JSON_UNESCAPED_UNICODE));
    }

    public static function receptionRequiresApproval(Reception $reception): bool
    {
        $enabled = array_map('mb_strtolower', self::enabledServices());
        if ($enabled === []) {
            return false;
        }

        $candidates = array_filter([
            trim((string) $reception->service_type),
            trim((string) $reception->repair_type),
        ]);

        foreach ($candidates as $name) {
            $lower = mb_strtolower($name);
            if (in_array($lower, $enabled, true)) {
                return true;
            }
            // soft match: "جراحی PCB" contains configured "جراحی"
            foreach ($enabled as $want) {
                if ($want !== '' && (str_contains($lower, $want) || str_contains($want, $lower))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * All selectable service + repair lookup names for settings UI.
     *
     * @return list<string>
     */
    public static function selectableServiceNames(): array
    {
        $names = array_merge(
            LookupOption::options('service_type'),
            LookupOption::options('repair_type'),
            self::DEFAULTS,
            self::enabledServices()
        );

        $names = array_values(array_unique(array_filter(array_map('trim', $names))));
        sort($names, SORT_STRING);

        return $names;
    }
}
