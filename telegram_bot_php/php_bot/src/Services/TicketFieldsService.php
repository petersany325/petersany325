<?php
declare(strict_types=1);

namespace HddLand\Bot\Services;

/**
 * Smart ticket field definitions (admin-managed).
 * Line format: key|type|EN|FA|required|ask_always
 * Types: name, phone, id, text, message
 */
final class TicketFieldsService
{
    public const TYPES = array('name', 'phone', 'id', 'text', 'message');

    /** @return list<array{key:string,type:string,en:string,fa:string,required:bool,ask_always:bool}> */
    public static function defaults(): array
    {
        return array(
            array('key' => 'contact_name', 'type' => 'name', 'en' => 'Full name', 'fa' => 'نام و نام خانوادگی', 'required' => true, 'ask_always' => true),
            array('key' => 'phone', 'type' => 'phone', 'en' => 'Mobile number (with country code)', 'fa' => 'شماره موبایل (با کد کشور)', 'required' => true, 'ask_always' => true),
            array('key' => 'customer_id', 'type' => 'id', 'en' => 'Customer / License / National ID', 'fa' => 'کد مشتری / لایسنس / کد ملی', 'required' => true, 'ask_always' => true),
            array('key' => 'drive_model', 'type' => 'text', 'en' => 'Hard drive model (e.g. WD20EFRX)', 'fa' => 'مدل هارد (مثلاً WD20EFRX)', 'required' => true, 'ask_always' => false),
            array('key' => 'error', 'type' => 'text', 'en' => 'Error / symptom', 'fa' => 'خطا / علائم مشکل', 'required' => true, 'ask_always' => false),
            array('key' => 'sediv_version', 'type' => 'text', 'en' => 'SeDiv version (if any)', 'fa' => 'نسخه SeDiv (اگر دارید)', 'required' => false, 'ask_always' => false),
            array('key' => 'problem', 'type' => 'message', 'en' => 'Describe your problem in detail', 'fa' => 'شرح کامل مشکل را بنویسید', 'required' => true, 'ask_always' => false),
        );
    }

    /** @return list<array{key:string,type:string,en:string,fa:string,required:bool,ask_always:bool}> */
    public static function all(): array
    {
        $raw = trim((string)cfg('ticket_fields', ''));
        if ($raw !== '') {
            $parsed = self::parse($raw);
            if ($parsed) {
                return self::ensureMessageField($parsed);
            }
        }
        // Backward compatible: build from old toggles + support_questions
        $out = array();
        if (!empty(cfg('ticket_ask_name', 1))) {
            $out[] = array(
                'key' => 'contact_name', 'type' => 'name',
                'en' => 'Full name', 'fa' => 'نام و نام خانوادگی',
                'required' => true,
                'ask_always' => !empty(cfg('ticket_always_ask_name', 1)),
            );
        }
        if (!empty(cfg('ticket_ask_phone', 1))) {
            $out[] = array(
                'key' => 'phone', 'type' => 'phone',
                'en' => 'Mobile number (with country code)', 'fa' => 'شماره موبایل (با کد کشور)',
                'required' => true,
                'ask_always' => !empty(cfg('ticket_always_ask_phone', 1)),
            );
        }
        if (!empty(cfg('ticket_ask_id', 1))) {
            $out[] = array(
                'key' => 'customer_id', 'type' => 'id',
                'en' => 'Customer / License / National ID', 'fa' => 'کد مشتری / لایسنس / کد ملی',
                'required' => !empty(cfg('ticket_id_required', 1)),
                'ask_always' => !empty(cfg('ticket_always_ask_id', 1)),
            );
        }
        $qraw = trim((string)cfg('support_questions', ''));
        if ($qraw !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $qraw) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $parts = array_map('trim', explode('|', $line));
                if (count($parts) < 3) {
                    continue;
                }
                $out[] = array(
                    'key' => preg_replace('/[^a-z0-9_]/i', '_', $parts[0]) ?: ('q' . (count($out) + 1)),
                    'type' => 'text',
                    'en' => $parts[1],
                    'fa' => $parts[2] !== '' ? $parts[2] : $parts[1],
                    'required' => !isset($parts[3]) || strtolower($parts[3]) !== '0',
                    'ask_always' => false,
                );
            }
        } else {
            foreach (self::defaults() as $d) {
                if (in_array($d['type'], array('text', 'message'), true)) {
                    $out[] = $d;
                }
            }
        }
        return self::ensureMessageField($out ?: self::defaults());
    }

    /** @param list<array<string,mixed>> $fields */
    private static function ensureMessageField(array $fields): array
    {
        foreach ($fields as $f) {
            if (($f['type'] ?? '') === 'message') {
                return $fields;
            }
        }
        $fields[] = array(
            'key' => 'problem', 'type' => 'message',
            'en' => 'Describe your problem in detail',
            'fa' => 'شرح کامل مشکل را بنویسید',
            'required' => true, 'ask_always' => false,
        );
        return $fields;
    }

    /** @return list<array{key:string,type:string,en:string,fa:string,required:bool,ask_always:bool}> */
    public static function parse(string $raw): array
    {
        $out = array();
        foreach (preg_split('/\r\n|\n|\r/', $raw) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) < 4) {
                continue;
            }
            $type = strtolower($parts[1]);
            if (!in_array($type, self::TYPES, true)) {
                $type = 'text';
            }
            $out[] = array(
                'key' => preg_replace('/[^a-z0-9_]/i', '_', $parts[0]) ?: ('f' . (count($out) + 1)),
                'type' => $type,
                'en' => $parts[2],
                'fa' => $parts[3] !== '' ? $parts[3] : $parts[2],
                'required' => !isset($parts[4]) || !in_array(strtolower($parts[4]), array('0', 'no', 'false'), true),
                'ask_always' => isset($parts[5]) && in_array(strtolower($parts[5]), array('1', 'yes', 'true'), true),
            );
        }
        return $out;
    }

    /** @param list<array<string,mixed>> $fields */
    public static function serialize(array $fields): string
    {
        $lines = array('# key|type|EN|FA|required|ask_always');
        foreach ($fields as $f) {
            $lines[] = implode('|', array(
                $f['key'],
                $f['type'],
                $f['en'],
                $f['fa'],
                !empty($f['required']) ? '1' : '0',
                !empty($f['ask_always']) ? '1' : '0',
            ));
        }
        return implode("\n", $lines);
    }

    /** @param list<array<string,mixed>> $fields */
    public static function save(array $fields): void
    {
        if (!function_exists('save_bot_config')) {
            throw new \RuntimeException('save_bot_config unavailable');
        }
        $cfg = merge_bot_defaults_into_config(bot_config());
        $cfg['ticket_fields'] = self::serialize($fields);
        // Keep legacy mirrors in sync for old UI
        $cfg['ticket_ask_name'] = 0;
        $cfg['ticket_ask_phone'] = 0;
        $cfg['ticket_ask_id'] = 0;
        $q = array();
        foreach ($fields as $f) {
            if ($f['type'] === 'name') {
                $cfg['ticket_ask_name'] = 1;
                $cfg['ticket_always_ask_name'] = !empty($f['ask_always']) ? 1 : 0;
            } elseif ($f['type'] === 'phone') {
                $cfg['ticket_ask_phone'] = 1;
                $cfg['ticket_always_ask_phone'] = !empty($f['ask_always']) ? 1 : 0;
            } elseif ($f['type'] === 'id') {
                $cfg['ticket_ask_id'] = 1;
                $cfg['ticket_always_ask_id'] = !empty($f['ask_always']) ? 1 : 0;
                $cfg['ticket_id_required'] = !empty($f['required']) ? 1 : 0;
            } elseif ($f['type'] === 'text') {
                $q[] = $f['key'] . '|' . $f['en'] . '|' . $f['fa'] . '|' . (!empty($f['required']) ? '1' : '0');
            }
        }
        $cfg['support_questions'] = implode("\n", $q);
        save_bot_config($cfg);
    }
}
