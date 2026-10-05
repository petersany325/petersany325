<?php

namespace App\Support;

use App\Models\AppSetting;

class AttendanceSettings
{
    public static function all(): array
    {
        return [
            'enabled' => self::enabled(),
            'require_gps' => self::requireGps(),
            'require_inside_geofence' => self::requireInsideGeofence(),
            'office_lat' => self::officeLat(),
            'office_lng' => self::officeLng(),
            'office_label' => self::officeLabel(),
            'geofence_radius_m' => self::geofenceRadiusM(),
            'max_gps_accuracy_m' => self::maxGpsAccuracyM(),
            'allow_selfie' => self::allowSelfie(),
            'allow_otp' => self::allowOtp(),
            'require_enrolled_photo' => self::requireEnrolledPhoto(),
            'work_start' => self::workStart(),
            'work_end' => self::workEnd(),
            'late_after_minutes' => self::lateAfterMinutes(),
            'min_minutes_between_punches' => self::minMinutesBetweenPunches(),
            'bind_device' => self::bindDevice(),
        ];
    }

    public static function enabled(): bool
    {
        return AppSetting::getValue('attendance_enabled', '1') === '1';
    }

    public static function requireGps(): bool
    {
        return AppSetting::getValue('attendance_require_gps', '1') === '1';
    }

    public static function requireInsideGeofence(): bool
    {
        return AppSetting::getValue('attendance_require_geofence', '1') === '1';
    }

    public static function officeLat(): ?float
    {
        $v = AppSetting::getValue('attendance_office_lat');

        return $v !== null && $v !== '' ? (float) $v : null;
    }

    public static function officeLng(): ?float
    {
        $v = AppSetting::getValue('attendance_office_lng');

        return $v !== null && $v !== '' ? (float) $v : null;
    }

    public static function officeLabel(): string
    {
        return trim((string) AppSetting::getValue('attendance_office_label', ''));
    }

    public static function geofenceRadiusM(): int
    {
        return max(20, min(5000, (int) AppSetting::getValue('attendance_geofence_radius_m', '80')));
    }

    public static function maxGpsAccuracyM(): int
    {
        return max(5, min(500, (int) AppSetting::getValue('attendance_max_gps_accuracy_m', '50')));
    }

    public static function allowSelfie(): bool
    {
        return AppSetting::getValue('attendance_allow_selfie', '1') === '1';
    }

    public static function allowOtp(): bool
    {
        return AppSetting::getValue('attendance_allow_otp', '1') === '1';
    }

    public static function requireEnrolledPhoto(): bool
    {
        return AppSetting::getValue('attendance_require_enrolled_photo', '1') === '1';
    }

    public static function workStart(): string
    {
        return AppSetting::getValue('attendance_work_start', '09:00') ?: '09:00';
    }

    public static function workEnd(): string
    {
        return AppSetting::getValue('attendance_work_end', '18:00') ?: '18:00';
    }

    public static function lateAfterMinutes(): int
    {
        return max(0, min(180, (int) AppSetting::getValue('attendance_late_after_minutes', '15')));
    }

    public static function minMinutesBetweenPunches(): int
    {
        return max(0, min(120, (int) AppSetting::getValue('attendance_min_minutes_between', '2')));
    }

    public static function bindDevice(): bool
    {
        return AppSetting::getValue('attendance_bind_device', '0') === '1';
    }

    public static function save(array $data): void
    {
        AppSetting::setValue('attendance_enabled', ! empty($data['enabled']) ? '1' : '0');
        AppSetting::setValue('attendance_require_gps', ! empty($data['require_gps']) ? '1' : '0');
        AppSetting::setValue('attendance_require_geofence', ! empty($data['require_inside_geofence']) ? '1' : '0');
        AppSetting::setValue('attendance_office_lat', isset($data['office_lat']) && $data['office_lat'] !== '' ? (string) $data['office_lat'] : '');
        AppSetting::setValue('attendance_office_lng', isset($data['office_lng']) && $data['office_lng'] !== '' ? (string) $data['office_lng'] : '');
        AppSetting::setValue('attendance_office_label', mb_substr(trim((string) ($data['office_label'] ?? '')), 0, 255));
        AppSetting::setValue('attendance_geofence_radius_m', (string) max(20, min(5000, (int) ($data['geofence_radius_m'] ?? 80))));
        AppSetting::setValue('attendance_max_gps_accuracy_m', (string) max(5, min(500, (int) ($data['max_gps_accuracy_m'] ?? 50))));
        AppSetting::setValue('attendance_allow_selfie', ! empty($data['allow_selfie']) ? '1' : '0');
        AppSetting::setValue('attendance_allow_otp', ! empty($data['allow_otp']) ? '1' : '0');
        AppSetting::setValue('attendance_require_enrolled_photo', ! empty($data['require_enrolled_photo']) ? '1' : '0');
        AppSetting::setValue('attendance_work_start', preg_match('/^\d{2}:\d{2}$/', (string) ($data['work_start'] ?? '')) ? $data['work_start'] : '09:00');
        AppSetting::setValue('attendance_work_end', preg_match('/^\d{2}:\d{2}$/', (string) ($data['work_end'] ?? '')) ? $data['work_end'] : '18:00');
        AppSetting::setValue('attendance_late_after_minutes', (string) max(0, min(180, (int) ($data['late_after_minutes'] ?? 15))));
        AppSetting::setValue('attendance_min_minutes_between', (string) max(0, min(120, (int) ($data['min_minutes_between_punches'] ?? 2))));
        AppSetting::setValue('attendance_bind_device', ! empty($data['bind_device']) ? '1' : '0');
    }
}
