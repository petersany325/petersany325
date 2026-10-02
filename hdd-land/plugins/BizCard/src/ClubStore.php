<?php

namespace Plugins\BizCard\src;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClubStore
{
    public static function ensureSchema(): void
    {
        try {
            if (! class_exists(Schema::class) || ! class_exists(DB::class)) {
                return;
            }
            if (! Schema::hasTable('biz_card_club_members')) {
                Schema::create('biz_card_club_members', function ($table) {
                    $table->id();
                    $table->string('name', 120)->nullable();
                    $table->string('phone', 20)->index();
                    $table->string('status', 20)->default('pending')->index();
                    $table->string('token', 64)->nullable()->unique();
                    $table->timestamp('requested_at')->nullable();
                    $table->timestamp('confirmed_at')->nullable();
                    $table->string('source', 40)->default('card');
                    $table->text('notes')->nullable();
                    $table->timestamps();
                });
            }
            if (! Schema::hasTable('biz_card_sms_logs')) {
                Schema::create('biz_card_sms_logs', function ($table) {
                    $table->id();
                    $table->string('phone', 20)->index();
                    $table->string('type', 30)->default('custom')->index();
                    $table->text('message')->nullable();
                    $table->string('status', 20)->default('queued');
                    $table->string('provider', 40)->nullable();
                    $table->text('response')->nullable();
                    $table->timestamps();
                });
            }
        } catch (\Throwable) {
            //
        }
    }

    /**
     * @return array{ok:bool,member:?object,error?:string,created?:bool}
     */
    public static function requestJoin(string $name, string $phone, string $source = 'card'): array
    {
        static::ensureSchema();
        $phone = SmsSender::normalizePhone($phone);
        $name = mb_substr(trim($name), 0, 120);
        if (! SmsSender::isMobile($phone)) {
            return ['ok' => false, 'member' => null, 'error' => 'شماره موبایل معتبر وارد کنید (مثال ۰۹۱۲۱۲۳۴۵۶۷).'];
        }

        try {
            if (! Schema::hasTable('biz_card_club_members')) {
                return ['ok' => false, 'member' => null, 'error' => 'جدول باشگاه مشتری هنوز ساخته نشده است.'];
            }

            $recent = DB::table('biz_card_club_members')
                ->where('phone', $phone)
                ->where('requested_at', '>=', now()->subHour())
                ->count();
            if ($recent >= 3) {
                return ['ok' => false, 'member' => null, 'error' => 'تعداد درخواست برای این شماره زیاد است. کمی بعد دوباره تلاش کنید.'];
            }

            $row = DB::table('biz_card_club_members')->where('phone', $phone)->orderByDesc('id')->first();
            $token = bin2hex(random_bytes(16));
            $now = now();

            if ($row && ($row->status ?? '') === 'confirmed') {
                return ['ok' => true, 'member' => $row, 'created' => false, 'already' => true];
            }

            if ($row) {
                DB::table('biz_card_club_members')->where('id', $row->id)->update([
                    'name' => $name !== '' ? $name : $row->name,
                    'status' => 'pending',
                    'token' => $token,
                    'requested_at' => $now,
                    'source' => $source,
                    'updated_at' => $now,
                ]);
                $member = DB::table('biz_card_club_members')->where('id', $row->id)->first();

                return ['ok' => true, 'member' => $member, 'created' => false];
            }

            $id = DB::table('biz_card_club_members')->insertGetId([
                'name' => $name,
                'phone' => $phone,
                'status' => 'pending',
                'token' => $token,
                'requested_at' => $now,
                'source' => $source,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return ['ok' => true, 'member' => DB::table('biz_card_club_members')->where('id', $id)->first(), 'created' => true];
        } catch (\Throwable $e) {
            return ['ok' => false, 'member' => null, 'error' => 'ثبت درخواست ممکن نشد.'];
        }
    }

    public static function findByToken(string $token): ?object
    {
        static::ensureSchema();
        $token = trim($token);
        if ($token === '' || ! Schema::hasTable('biz_card_club_members')) {
            return null;
        }
        try {
            return DB::table('biz_card_club_members')->where('token', $token)->first();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function confirm(string $token): ?object
    {
        $row = static::findByToken($token);
        if (! $row) {
            return null;
        }
        try {
            DB::table('biz_card_club_members')->where('id', $row->id)->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]);

            return DB::table('biz_card_club_members')->where('id', $row->id)->first();
        } catch (\Throwable) {
            return $row;
        }
    }

    public static function confirmById(int $id): bool
    {
        static::ensureSchema();
        try {
            return DB::table('biz_card_club_members')->where('id', $id)->update([
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'updated_at' => now(),
            ]) > 0;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function deleteMember(int $id): void
    {
        try {
            if (Schema::hasTable('biz_card_club_members')) {
                DB::table('biz_card_club_members')->where('id', $id)->delete();
            }
        } catch (\Throwable) {
            //
        }
    }

    /** @return list<object> */
    public static function members(int $limit = 80): array
    {
        static::ensureSchema();
        try {
            if (! Schema::hasTable('biz_card_club_members')) {
                return [];
            }

            return DB::table('biz_card_club_members')->orderByDesc('id')->limit($limit)->get()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function counts(): array
    {
        static::ensureSchema();
        $empty = ['all' => 0, 'pending' => 0, 'confirmed' => 0];
        try {
            if (! Schema::hasTable('biz_card_club_members')) {
                return $empty;
            }
            $rows = DB::table('biz_card_club_members')->select('status', DB::raw('count(*) as c'))->groupBy('status')->get();
            foreach ($rows as $r) {
                $empty['all'] += (int) $r->c;
                if (($r->status ?? '') === 'pending') {
                    $empty['pending'] = (int) $r->c;
                }
                if (($r->status ?? '') === 'confirmed') {
                    $empty['confirmed'] = (int) $r->c;
                }
            }
        } catch (\Throwable) {
            //
        }

        return $empty;
    }

    public static function logSms(string $phone, string $type, string $message, string $status, string $provider, string $response): void
    {
        static::ensureSchema();
        try {
            if (! Schema::hasTable('biz_card_sms_logs')) {
                return;
            }
            DB::table('biz_card_sms_logs')->insert([
                'phone' => $phone,
                'type' => $type,
                'message' => mb_substr($message, 0, 1000),
                'status' => $status,
                'provider' => $provider,
                'response' => mb_substr($response, 0, 1000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable) {
            //
        }
    }

    /** @return list<object> */
    public static function smsLogs(int $limit = 40): array
    {
        static::ensureSchema();
        try {
            if (! Schema::hasTable('biz_card_sms_logs')) {
                return [];
            }

            return DB::table('biz_card_sms_logs')->orderByDesc('id')->limit($limit)->get()->all();
        } catch (\Throwable) {
            return [];
        }
    }

    public static function confirmUrl(object $member): string
    {
        $token = (string) ($member->token ?? '');

        return function_exists('url') ? url('/card/club/confirm/'.$token) : '/card/club/confirm/'.$token;
    }
}
