<?php

namespace App\Services;

use App\Models\AttendanceEvent;
use App\Models\AttendanceOtp;
use App\Models\AttendanceProfile;
use App\Models\AttendancePunchLink;
use App\Models\User;
use App\Support\AttendanceSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * ثبت اولیه اولین ورود: سلفی با تشخیص چهره + GPS موبایل.
     * سلفی می‌رود برای تأیید ادمین؛ منوی کار بعد از این مرحله باز می‌شود.
     *
     * @param  array{latitude:mixed,longitude:mixed,accuracy_m?:mixed,device_fingerprint?:?string,face_detected?:mixed}  $geo
     */
    public function completeOnboarding(User $user, \Illuminate\Http\UploadedFile $selfie, array $geo): AttendanceProfile
    {
        $lat = isset($geo['latitude']) && $geo['latitude'] !== '' ? (float) $geo['latitude'] : null;
        $lng = isset($geo['longitude']) && $geo['longitude'] !== '' ? (float) $geo['longitude'] : null;
        $acc = isset($geo['accuracy_m']) && $geo['accuracy_m'] !== '' ? (float) $geo['accuracy_m'] : null;
        $faceDetected = ! empty($geo['face_detected']);

        if (! $faceDetected) {
            throw ValidationException::withMessages([
                'photo' => 'چهره در سلفی تشخیص داده نشد. صورت را روبه‌رو و در نور کافی بگیرید.',
            ]);
        }

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
            'face_detected' => true,
            'selfie_status' => AttendanceProfile::SELFIE_PENDING,
            'selfie_approved_at' => null,
            'selfie_approved_by' => null,
            'selfie_reject_reason' => null,
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

    public function approveSelfie(AttendanceProfile $profile, User $admin): AttendanceProfile
    {
        if (! $profile->hasReferencePhoto()) {
            throw ValidationException::withMessages(['photo' => 'عکس مرجع برای تأیید وجود ندارد.']);
        }
        $profile->forceFill([
            'selfie_status' => AttendanceProfile::SELFIE_APPROVED,
            'selfie_approved_at' => now(),
            'selfie_approved_by' => $admin->id,
            'selfie_reject_reason' => null,
            'is_active' => true,
        ])->save();

        return $profile->fresh();
    }

    /**
     * پس از تأیید سلفی: اعلان داخل‌برنامه + SMS با لینک ورود امروز.
     *
     * @return array{ok:bool,message:string,url?:string,debug_code?:string}
     */
    public function notifySelfieApproved(User $user, NiazpardazSmsService $sms): array
    {
        $link = $this->createPunchLink($user, AttendanceEvent::TYPE_IN, 60 * 12); // تا ۱۲ ساعت
        $url = route('attendance.link.show', ['token' => $link->token]);
        $shop = \App\Models\AppSetting::getValue('invoice_shop_name', config('app.name'));
        $message = "سلفی حضور شما در {$shop} تأیید شد. برای ورود امروز روی لینک بزنید:\n{$url}";

        $phone = User::normalizePhone($user->phone);
        if (! $phone) {
            return ['ok' => false, 'message' => 'موبایل کارمند ثبت نشده؛ لینک ساخته شد ولی SMS ارسال نشد.', 'url' => $url];
        }

        $result = $sms->send($phone, $message);
        $result['url'] = $url;

        return $result;
    }

    public function createPunchLink(User $user, string $purpose, int $ttlMinutes = 15): AttendancePunchLink
    {
        if (! in_array($purpose, [AttendanceEvent::TYPE_IN, AttendanceEvent::TYPE_OUT], true)) {
            throw ValidationException::withMessages(['purpose' => 'نوع لینک نامعتبر است.']);
        }

        AttendancePunchLink::query()
            ->where('user_id', $user->id)
            ->where('purpose', $purpose)
            ->whereNull('used_at')
            ->delete();

        return AttendancePunchLink::create([
            'user_id' => $user->id,
            'token' => Str::random(48),
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(max(5, $ttlMinutes)),
        ]);
    }

    public function sendPunchLinkSms(User $user, string $purpose, NiazpardazSmsService $sms): array
    {
        if (! AttendanceSettings::allowOtp() && ! AttendanceSettings::allowSelfie()) {
            throw ValidationException::withMessages(['method' => 'ارسال لینک حضور فعلاً غیرفعال است.']);
        }

        $state = $this->todayState($user);
        if ($purpose === AttendanceEvent::TYPE_IN && $state['open']) {
            throw ValidationException::withMessages(['purpose' => 'امروز قبلاً ورود زده‌اید؛ برای خروج لینک بگیرید.']);
        }
        if ($purpose === AttendanceEvent::TYPE_OUT && ! $state['open']) {
            throw ValidationException::withMessages(['purpose' => 'ورود بازی نیست؛ ابتدا ورود بزنید.']);
        }

        $link = $this->createPunchLink($user, $purpose, 15);
        $url = route('attendance.link.show', ['token' => $link->token]);
        $label = $purpose === AttendanceEvent::TYPE_OUT ? 'خروج' : 'ورود';
        $shop = \App\Models\AppSetting::getValue('invoice_shop_name', config('app.name'));
        $message = "لینک {$label} حضور {$shop} (۱۵ دقیقه):\n{$url}";

        $phone = User::normalizePhone($user->phone);
        if (! $phone) {
            throw ValidationException::withMessages(['phone' => 'موبایل کارمند برای ارسال لینک ثبت نشده است.']);
        }

        $result = $sms->send($phone, $message);
        $result['url'] = $url;

        return $result;
    }

    public function findValidPunchLink(string $token): AttendancePunchLink
    {
        $link = AttendancePunchLink::query()->where('token', $token)->first();
        if (! $link || ! $link->isValid()) {
            throw ValidationException::withMessages([
                'token' => 'لینک منقضی شده یا قبلاً استفاده شده است. دوباره درخواست دهید.',
            ]);
        }

        return $link;
    }

    public function markPunchLinkUsed(AttendancePunchLink $link): void
    {
        $link->forceFill(['used_at' => now()])->save();
    }

    public function rejectSelfie(AttendanceProfile $profile, User $admin, ?string $reason = null): AttendanceProfile
    {
        $profile->forceFill([
            'selfie_status' => AttendanceProfile::SELFIE_REJECTED,
            'selfie_approved_at' => null,
            'selfie_approved_by' => $admin->id,
            'selfie_reject_reason' => mb_substr(trim((string) $reason) ?: 'رد توسط ادمین', 0, 255),
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
            'face_detected' => true,
            'selfie_status' => AttendanceProfile::SELFIE_APPROVED,
            'selfie_approved_at' => now(),
            'selfie_approved_by' => $admin->id,
            'selfie_reject_reason' => null,
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
     * @param  array{method:string,otp_code?:?string,latitude?:mixed,longitude?:mixed,accuracy_m?:mixed,device_fingerprint?:?string,note?:?string,face_detected?:mixed,photo_confirmed?:mixed,punch_link_token?:?string}  $data
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
        if ($method === AttendanceEvent::METHOD_PHOTO_CONFIRM && ! AttendanceSettings::allowSelfie()) {
            throw ValidationException::withMessages(['method' => 'تأیید با عکس مرجع غیرفعال است.']);
        }
        $allowed = [
            AttendanceEvent::METHOD_SELFIE,
            AttendanceEvent::METHOD_OTP,
            AttendanceEvent::METHOD_PHOTO_CONFIRM,
            AttendanceEvent::METHOD_SMS_LINK,
        ];
        if (! in_array($method, $allowed, true)) {
            throw ValidationException::withMessages(['method' => 'روش تأیید را انتخاب کنید.']);
        }

        $state = $this->todayState($user);
        if ($type === AttendanceEvent::TYPE_IN && $state['open']) {
            throw ValidationException::withMessages(['type' => 'امروز قبلاً ورود زده‌اید؛ ابتدا خروج ثبت کنید. وضعیت هر روز از نیمه‌شب ریست می‌شود.']);
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
        $link = null;
        if ($method === AttendanceEvent::METHOD_SMS_LINK) {
            $token = trim((string) ($data['punch_link_token'] ?? ''));
            $link = $this->findValidPunchLink($token);
            if ((int) $link->user_id !== (int) $user->id) {
                throw ValidationException::withMessages(['token' => 'لینک متعلق به این کاربر نیست.']);
            }
            if ($link->purpose !== $type) {
                throw ValidationException::withMessages(['token' => 'این لینک برای '.($link->purpose === AttendanceEvent::TYPE_OUT ? 'خروج' : 'ورود').' است.']);
            }
        }

        if (in_array($method, [AttendanceEvent::METHOD_SELFIE, AttendanceEvent::METHOD_PHOTO_CONFIRM], true)) {
            if (! $profile || ! $profile->isSelfieApproved()) {
                $msg = 'سلفی شما هنوز توسط ادمین تأیید نشده است.';
                if ($profile?->isSelfiePending()) {
                    $msg = 'سلفی شما در انتظار تأیید ادمین است. تا تأیید، ورود با سلفی ممکن نیست.';
                } elseif ($profile?->isSelfieRejected()) {
                    $msg = 'سلفی رد شده است'.($profile->selfie_reject_reason ? ' ('.$profile->selfie_reject_reason.')' : '').'. دوباره از ثبت اولیه/ادمین عکس بگیرید.';
                }
                throw ValidationException::withMessages(['photo' => $msg]);
            }
        }

        if ($method === AttendanceEvent::METHOD_SELFIE && empty($data['face_detected'])) {
            throw ValidationException::withMessages([
                'photo' => 'چهره در سلفی تشخیص داده نشد. صورت را روبه‌روی دوربین بگیرید.',
            ]);
        }
        if ($method === AttendanceEvent::METHOD_PHOTO_CONFIRM && empty($data['photo_confirmed'])) {
            throw ValidationException::withMessages([
                'photo' => 'برای ورود باید عکس تأییدشده خود را مشاهده و تأیید کنید.',
            ]);
        }

        $otpVerified = false;
        if ($method === AttendanceEvent::METHOD_OTP) {
            $this->verifyOtp($user, $type, (string) ($data['otp_code'] ?? ''));
            $otpVerified = true;
        }
        if ($method === AttendanceEvent::METHOD_SMS_LINK) {
            $otpVerified = true;
        }

        $photoPath = null;
        if ($method === AttendanceEvent::METHOD_SELFIE) {
            if (! $selfie) {
                throw ValidationException::withMessages(['photo' => 'سلفی لحظه‌ای الزامی است (از گالری انتخاب نکنید).']);
            }
            $photoPath = $selfie->store('attendance/punches/'.$user->id.'/'.now()->format('Ymd'), 'local');
        } elseif (
            in_array($method, [AttendanceEvent::METHOD_PHOTO_CONFIRM, AttendanceEvent::METHOD_SMS_LINK], true)
            && $profile?->reference_photo_path
            && ($method === AttendanceEvent::METHOD_PHOTO_CONFIRM || ! empty($data['photo_confirmed']))
        ) {
            // کپی از عکس مرجع تأییدشده برای آرشیو رویداد
            $ext = pathinfo($profile->reference_photo_path, PATHINFO_EXTENSION) ?: 'jpg';
            $dest = 'attendance/punches/'.$user->id.'/'.now()->format('Ymd').'/confirm_'.Str::random(8).'.'.$ext;
            if (Storage::disk('local')->exists($profile->reference_photo_path)) {
                Storage::disk('local')->copy($profile->reference_photo_path, $dest);
                $photoPath = $dest;
            }
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

        $event = AttendanceEvent::create([
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

        if ($link) {
            $this->markPunchLinkUsed($link);
        }

        return $event;
    }
}
