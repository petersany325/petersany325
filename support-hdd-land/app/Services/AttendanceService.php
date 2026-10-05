<?php

namespace App\Services;

use App\Models\AttendanceEvent;
use App\Models\AttendanceOtp;
use App\Models\AttendanceProfile;
use App\Models\User;
use App\Support\AttendanceSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function profileFor(User $user): ?AttendanceProfile
    {
        return AttendanceProfile::query()->where('user_id', $user->id)->first();
    }

    public function ensureProfile(User $user): AttendanceProfile
    {
        return AttendanceProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['is_active' => true]
        );
    }

    public function hasCompletedOnboarding(User $user): bool
    {
        $profile = $this->profileFor($user);

        return $profile?->hasCompletedOnboarding() ?? false;
    }

    /**
     * ثبت اولیه اولین ورود: سلفی + GPS موبایل → باز شدن منوی کار.
     *
     * @param  array{latitude:mixed,longitude:mixed,accuracy_m?:mixed,device_fingerprint?:?string}  $geo
     */
    public function completeOnboarding(User $user, \Illuminate\Http\UploadedFile $selfie, array $geo): AttendanceProfile
    {
        $lat = isset($geo['latitude']) && $geo['latitude'] !== '' ? (float) $geo['latitude'] : null;
        $lng = isset($geo['longitude']) && $geo['longitude'] !== '' ? (float) $geo['longitude'] : null;
        $acc = isset($geo['accuracy_m']) && $geo['accuracy_m'] !== '' ? (float) $geo['accuracy_m'] : null;

        if ($lat === null || $lng === null) {
            throw ValidationException::withMessages([
                'latitude' => 'موقعیت GPS موبایل الزامی است. دسترسی مکان را در گوشی فعال کنید.',
            ]);
        }
        if ($acc !== null && $acc > AttendanceSettings::maxGpsAccuracyM()) {
            throw ValidationException::withMessages([
                'accuracy_m' => 'دقت GPS کافی نیست (حداکثر '.AttendanceSettings::maxGpsAccuracyM().' متر). در فضای باز دوباره تلاش کنید.',
            ]);
        }

        $profile = $this->ensureProfile($user);
        if ($profile->reference_photo_path) {
            Storage::disk('local')->delete($profile->reference_photo_path);
        }
        $path = $selfie->store('attendance/refs/'.$user->id, 'local');

        $device = trim((string) ($geo['device_fingerprint'] ?? ''));
        $profile->forceFill([
            'reference_photo_path' => $path,
            'enrolled_at' => now(),
            'enrolled_by' => $user->id,
            'phone_gps_lat' => $lat,
            'phone_gps_lng' => $lng,
            'phone_gps_accuracy_m' => $acc,
            'phone_gps_captured_at' => now(),
            'onboarding_completed_at' => now(),
            'device_fingerprint' => $device !== '' ? $device : $profile->device_fingerprint,
            'is_active' => true,
        ])->save();

        return $profile->fresh();
    }

    /** @return array{open:bool,last:?AttendanceEvent} */
    public function todayState(User $user, ?Carbon $now = null): array
    {
        $now = $now ?: now();
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->endOfDay();

        $last = AttendanceEvent::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', AttendanceEvent::STATUS_REJECTED)
            ->whereBetween('occurred_at', [$start, $end])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->first();

        $open = $last && $last->type === AttendanceEvent::TYPE_IN;

        return ['open' => $open, 'last' => $last];
    }

    /**
     * Distinct calendar days with at least one accepted check-in in range.
     */
    public function presentDaysCount(User $user, Carbon $from, Carbon $to): int
    {
        return (int) AttendanceEvent::query()
            ->where('user_id', $user->id)
            ->where('type', AttendanceEvent::TYPE_IN)
            ->where('status', AttendanceEvent::STATUS_ACCEPTED)
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('COUNT(DISTINCT DATE(occurred_at)) as c')
            ->value('c');
    }

    /**
     * @return list<array{date:string,check_in:?string,check_out:?string,late:bool,flagged:bool}>
     */
    public function dailySummary(User $user, Carbon $from, Carbon $to): array
    {
        $events = AttendanceEvent::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', AttendanceEvent::STATUS_REJECTED)
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->orderBy('occurred_at')
            ->get();

        $byDay = [];
        foreach ($events as $event) {
            $day = $event->occurred_at->toDateString();
            $byDay[$day] ??= [
                'date' => $day,
                'check_in' => null,
                'check_out' => null,
                'late' => false,
                'flagged' => false,
            ];
            if ($event->type === AttendanceEvent::TYPE_IN && $byDay[$day]['check_in'] === null) {
                $byDay[$day]['check_in'] = $event->occurred_at->format('H:i');
                $byDay[$day]['late'] = $this->isLate($event->occurred_at);
            }
            if ($event->type === AttendanceEvent::TYPE_OUT) {
                $byDay[$day]['check_out'] = $event->occurred_at->format('H:i');
            }
            if ($event->status === AttendanceEvent::STATUS_FLAGGED) {
                $byDay[$day]['flagged'] = true;
            }
        }

        ksort($byDay);

        return array_values($byDay);
    }

    public function isLate(Carbon $at): bool
    {
        $start = AttendanceSettings::workStart();
        [$h, $m] = array_map('intval', explode(':', $start));
        $threshold = $at->copy()->startOfDay()->setTime($h, $m)->addMinutes(AttendanceSettings::lateAfterMinutes());

        return $at->gt($threshold);
    }

    public function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $φ1 = deg2rad($lat1);
        $φ2 = deg2rad($lat2);
        $Δφ = deg2rad($lat2 - $lat1);
        $Δλ = deg2rad($lng2 - $lng1);
        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;

        return 2 * $earth * asin(min(1, sqrt($a)));
    }

    /**
     * @param  array{latitude?:mixed,longitude?:mixed,accuracy_m?:mixed,device_fingerprint?:?string,note?:?string}  $geo
     * @return array{latitude:?float,longitude:?float,accuracy_m:?float,distance_m:?int,inside_geofence:?bool,flags:list<string>}
     */
    public function validateGeo(array $geo): array
    {
        $flags = [];
        $lat = isset($geo['latitude']) && $geo['latitude'] !== '' ? (float) $geo['latitude'] : null;
        $lng = isset($geo['longitude']) && $geo['longitude'] !== '' ? (float) $geo['longitude'] : null;
        $acc = isset($geo['accuracy_m']) && $geo['accuracy_m'] !== '' ? (float) $geo['accuracy_m'] : null;

        if (AttendanceSettings::requireGps()) {
            if ($lat === null || $lng === null) {
                throw ValidationException::withMessages([
                    'latitude' => 'موقعیت GPS برای ثبت حضور الزامی است. اجازه دسترسی مکان را در موبایل فعال کنید.',
                ]);
            }
            if ($acc !== null && $acc > AttendanceSettings::maxGpsAccuracyM()) {
                throw ValidationException::withMessages([
                    'accuracy_m' => 'دقت GPS کافی نیست (حداکثر '.AttendanceSettings::maxGpsAccuracyM().' متر). در فضای باز دوباره تلاش کنید.',
                ]);
            }
        }

        $distance = null;
        $inside = null;
        $officeLat = AttendanceSettings::officeLat();
        $officeLng = AttendanceSettings::officeLng();

        if ($lat !== null && $lng !== null && $officeLat !== null && $officeLng !== null) {
            $distance = (int) round($this->distanceMeters($lat, $lng, $officeLat, $officeLng));
            $inside = $distance <= AttendanceSettings::geofenceRadiusM();
            if (AttendanceSettings::requireInsideGeofence() && ! $inside) {
                throw ValidationException::withMessages([
                    'latitude' => 'خارج از محدوده مجاز شرکت هستید (فاصله حدود '.$distance.' متر؛ شعاع مجاز '.AttendanceSettings::geofenceRadiusM().' متر).',
                ]);
            }
            if (! $inside) {
                $flags[] = 'خارج از محدوده GPS';
            }
        } elseif (AttendanceSettings::requireInsideGeofence() && AttendanceSettings::requireGps()) {
            throw ValidationException::withMessages([
                'office' => 'مختصات شرکت در تنظیمات حضور و غیاب هنوز تعریف نشده است. با مدیر تماس بگیرید.',
            ]);
        }

        return [
            'latitude' => $lat,
            'longitude' => $lng,
            'accuracy_m' => $acc,
            'distance_m' => $distance,
            'inside_geofence' => $inside,
            'flags' => $flags,
        ];
    }

    public function enrollPhoto(User $target, UploadedFile $photo, User $admin): AttendanceProfile
    {
        $profile = $this->ensureProfile($target);
        if ($profile->reference_photo_path) {
            Storage::disk('local')->delete($profile->reference_photo_path);
        }
        $path = $photo->store('attendance/refs/'.$target->id, 'local');
        $profile->forceFill([
            'reference_photo_path' => $path,
            'enrolled_at' => now(),
            'enrolled_by' => $admin->id,
            'is_active' => true,
        ])->save();

        return $profile->fresh();
    }

    public function sendPunchOtp(User $user, string $purpose, NiazpardazSmsService $sms): array
    {
        if (! AttendanceSettings::allowOtp()) {
            throw ValidationException::withMessages(['method' => 'روش OTP برای حضور و غیاب غیرفعال است.']);
        }
        $phone = User::normalizePhone($user->phone);
        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'موبایل کارمند برای ارسال رمز ثبت نشده است.']);
        }

        $code = (string) random_int(100000, 999999);
        AttendanceOtp::query()->where('user_id', $user->id)->where('purpose', $purpose)->delete();
        AttendanceOtp::create([
            'user_id' => $user->id,
            'phone' => $phone,
            'code' => $code,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(5),
            'attempts' => 0,
        ]);

        $label = $purpose === AttendanceEvent::TYPE_OUT ? 'خروج' : 'ورود';
        $shop = \App\Models\AppSetting::getValue('invoice_shop_name', config('app.name'));
        $message = "کد {$label} حضور {$shop}: {$code}";

        return $sms->send($phone, $message, $code);
    }

    public function verifyOtp(User $user, string $purpose, string $code): void
    {
        $row = AttendanceOtp::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->orderByDesc('id')
            ->first();

        if (! $row || $row->isExpired()) {
            throw ValidationException::withMessages(['otp_code' => 'کد منقضی شده یا یافت نشد. دوباره درخواست دهید.']);
        }
        if ($row->attempts >= 5) {
            throw ValidationException::withMessages(['otp_code' => 'تعداد تلاش بیش از حد. کد جدید بگیرید.']);
        }
        if (! hash_equals($row->code, trim($code))) {
            $row->increment('attempts');
            throw ValidationException::withMessages(['otp_code' => 'کد ورود نادرست است.']);
        }
        $row->delete();
    }

    /**
     * @param  array{method:string,otp_code?:?string,latitude?:mixed,longitude?:mixed,accuracy_m?:mixed,device_fingerprint?:?string,note?:?string}  $data
     */
    public function punch(User $user, string $type, array $data, ?UploadedFile $selfie, NiazpardazSmsService $sms): AttendanceEvent
    {
        if (! AttendanceSettings::enabled()) {
            throw ValidationException::withMessages(['attendance' => 'سیستم حضور و غیاب فعلاً غیرفعال است.']);
        }

        $method = $data['method'] ?? '';
        if ($method === AttendanceEvent::METHOD_SELFIE && ! AttendanceSettings::allowSelfie()) {
            throw ValidationException::withMessages(['method' => 'روش سلفی غیرفعال است.']);
        }
        if ($method === AttendanceEvent::METHOD_OTP && ! AttendanceSettings::allowOtp()) {
            throw ValidationException::withMessages(['method' => 'روش OTP غیرفعال است.']);
        }
        if (! in_array($method, [AttendanceEvent::METHOD_SELFIE, AttendanceEvent::METHOD_OTP], true)) {
            throw ValidationException::withMessages(['method' => 'روش تأیید را انتخاب کنید (سلفی یا OTP).']);
        }

        $state = $this->todayState($user);
        if ($type === AttendanceEvent::TYPE_IN && $state['open']) {
            throw ValidationException::withMessages(['type' => 'امروز قبلاً ورود زده‌اید؛ ابتدا خروج ثبت کنید.']);
        }
        if ($type === AttendanceEvent::TYPE_OUT && ! $state['open']) {
            throw ValidationException::withMessages(['type' => 'ورود بازی برای امروز نیست؛ ابتدا ورود بزنید.']);
        }

        if ($state['last'] && AttendanceSettings::minMinutesBetweenPunches() > 0) {
            $diff = $state['last']->occurred_at->diffInMinutes(now());
            if ($diff < AttendanceSettings::minMinutesBetweenPunches()) {
                throw ValidationException::withMessages([
                    'type' => 'حداقل '.AttendanceSettings::minMinutesBetweenPunches().' دقیقه بین دو ثبت فاصله لازم است.',
                ]);
            }
        }

        $profile = $this->profileFor($user);
        if (AttendanceSettings::requireEnrolledPhoto() && $method === AttendanceEvent::METHOD_SELFIE) {
            if (! $profile || ! $profile->hasReferencePhoto()) {
                throw ValidationException::withMessages([
                    'photo' => 'عکس مرجع شما هنوز توسط مدیر ثبت نشده است. با ادمین تماس بگیرید.',
                ]);
            }
        }

        $otpVerified = false;
        if ($method === AttendanceEvent::METHOD_OTP) {
            $this->verifyOtp($user, $type, (string) ($data['otp_code'] ?? ''));
            $otpVerified = true;
        }

        $photoPath = null;
        if ($method === AttendanceEvent::METHOD_SELFIE) {
            if (! $selfie) {
                throw ValidationException::withMessages(['photo' => 'سلفی لحظه‌ای الزامی است (از گالری انتخاب نکنید).']);
            }
            $photoPath = $selfie->store('attendance/punches/'.$user->id.'/'.now()->format('Ymd'), 'local');
        }

        $geo = $this->validateGeo($data);
        $flags = $geo['flags'];

        $device = trim((string) ($data['device_fingerprint'] ?? ''));
        if (AttendanceSettings::bindDevice() && $device !== '') {
            $profile = $this->ensureProfile($user);
            if ($profile->device_fingerprint && $profile->device_fingerprint !== $device) {
                $flags[] = 'دستگاه متفاوت با دستگاه ثبت‌شده';
            } elseif (! $profile->device_fingerprint) {
                $profile->forceFill(['device_fingerprint' => $device])->save();
            }
        }

        $status = AttendanceEvent::STATUS_ACCEPTED;
        $flagReason = null;
        if ($flags !== []) {
            $status = AttendanceEvent::STATUS_FLAGGED;
            $flagReason = implode('؛ ', $flags);
        }

        return AttendanceEvent::create([
            'user_id' => $user->id,
            'type' => $type,
            'method' => $method,
            'status' => $status,
            'latitude' => $geo['latitude'],
            'longitude' => $geo['longitude'],
            'accuracy_m' => $geo['accuracy_m'],
            'distance_m' => $geo['distance_m'],
            'inside_geofence' => $geo['inside_geofence'],
            'photo_path' => $photoPath,
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 500),
            'device_fingerprint' => $device !== '' ? $device : null,
            'otp_verified' => $otpVerified,
            'flag_reason' => $flagReason,
            'note' => trim((string) ($data['note'] ?? '')) ?: null,
            'occurred_at' => now(),
        ]);
    }
}
