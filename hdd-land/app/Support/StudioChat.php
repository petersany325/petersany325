<?php

namespace App\Support;

/**
 * Site chat. Answers come from the admin settings, seeded from the published site.
 * Prices stay unpublished until the owner writes one on the product page.
 */
class StudioChat
{
    public const KEY = 'studio_chat';

    public static function defaults(): array
    {
        $greeting = 'سلام. من راهنمای سایت فراز نت آمل هستم. دربارهٔ طراحی سایت، حسابداری HDD Land و هوش مصنوعی همین سایت جواب می‌دهم. اگر بخواهید با یک نفر حرف بزنید، فروش یا پشتیبانی را وصل می‌کنم.';

        return [
            'enabled' => true,
            'corner' => 'right',
            'button' => 'گفتگو',
            'title' => 'راهنمای فراز نت',
            'subtitle' => 'جواب از روی همین سایت',
            'placeholder' => 'سؤال خود را بنویسید',
            'greeting' => $greeting,
            'suggestions' => [
                'طراحی سایت چه خدماتی دارد؟',
                'حسابداری HDD Land چیست؟',
                'قیمت نرم‌افزار چقدر است؟',
                'چطور با پشتیبان حرف بزنم؟',
            ],
            'fallback' => 'این را از روی صفحات سایت مطمئن جواب نمی‌دهم. می‌توانید سؤال را دقیق‌تر بگویید، یا مستقیم به فروش یا پشتیبانی وصل شوید.',
            'avatar' => 'images/chat/guide.jpg',
            'show_sales' => true,
            'sales_label' => 'اتصال به فروش',
            'sales_url' => 'https://wa.me/989113100473',
            'consult_label' => 'مشاوره فروش',
            'consult_phone' => '09113100473',
            'show_support' => true,
            'support_label' => 'اتصال به پشتیبانی',
            'support_url' => '/design/support',
            'topics' => self::defaultTopics($greeting),
        ];
    }

    /** @return array<string, mixed> */
    public static function get(): array
    {
        $raw = [];
        try {
            $raw = SettingsStore::get(self::KEY, []);
        } catch (\Throwable) {
            $raw = [];
        }
        if (is_string($raw)) {
            $raw = json_decode($raw, true) ?: [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }

        $base = self::defaults();
        $s = array_merge($base, $raw);
        $s['enabled'] = SettingsStore::toBool($s['enabled'] ?? true, true);
        $s['show_sales'] = SettingsStore::toBool($s['show_sales'] ?? true, true);
        $s['show_support'] = SettingsStore::toBool($s['show_support'] ?? true, true);
        $s['corner'] = ($s['corner'] ?? '') === 'left' ? 'left' : 'right';
        $s['avatar'] = self::safeAvatar((string) ($s['avatar'] ?? $base['avatar']));
        $s['sales_url'] = self::safeUrl((string) ($s['sales_url'] ?? '')) ?: $base['sales_url'];
        $s['support_url'] = self::safeUrl((string) ($s['support_url'] ?? '')) ?: $base['support_url'];
        $s['consult_phone'] = self::phone((string) ($s['consult_phone'] ?? '')) ?: $base['consult_phone'];
        foreach (['button', 'title', 'subtitle', 'placeholder', 'greeting', 'fallback', 'sales_label', 'support_label', 'consult_label'] as $k) {
            $s[$k] = trim((string) ($s[$k] ?? ''));
            if ($s[$k] === '') {
                $s[$k] = $base[$k];
            }
        }
        $suggestions = [];
        foreach ((array) ($s['suggestions'] ?? []) as $line) {
            $line = mb_substr(trim((string) $line), 0, 80);
            if ($line !== '') {
                $suggestions[] = $line;
            }
            if (count($suggestions) >= 6) {
                break;
            }
        }
        $s['suggestions'] = $suggestions !== [] ? $suggestions : $base['suggestions'];
        $topics = self::topicsFrom($s['topics'] ?? []);
        $s['topics'] = $topics !== [] ? $topics : $base['topics'];

        return $s;
    }

    /** @param  array<string, mixed>  $d */
    public static function save(array $d): array
    {
        $base = self::defaults();
        $s = self::get();
        $s['enabled'] = ! empty($d['enabled']);
        $s['show_sales'] = ! empty($d['show_sales']);
        $s['show_support'] = ! empty($d['show_support']);
        $s['corner'] = (($d['corner'] ?? '') === 'left') ? 'left' : 'right';
        foreach ([
            'button' => 24, 'title' => 60, 'subtitle' => 80, 'placeholder' => 80,
            'greeting' => 400, 'fallback' => 400,
            'sales_label' => 40, 'support_label' => 40, 'consult_label' => 40,
        ] as $k => $max) {
            $s[$k] = mb_substr(trim((string) ($d[$k] ?? '')), 0, $max);
            if ($s[$k] === '') {
                $s[$k] = $base[$k];
            }
        }
        $s['sales_url'] = self::safeUrl((string) ($d['sales_url'] ?? '')) ?: $base['sales_url'];
        $s['support_url'] = self::safeUrl((string) ($d['support_url'] ?? '')) ?: $base['support_url'];
        $s['consult_phone'] = self::phone((string) ($d['consult_phone'] ?? '')) ?: $base['consult_phone'];
        if (isset($d['avatar'])) {
            $s['avatar'] = self::safeAvatar((string) $d['avatar']);
        }
        $lines = preg_split('/\n/u', (string) ($d['suggestions_text'] ?? '')) ?: [];
        $suggestions = [];
        foreach ($lines as $line) {
            $line = mb_substr(trim($line), 0, 80);
            if ($line !== '') {
                $suggestions[] = $line;
            }
            if (count($suggestions) >= 6) {
                break;
            }
        }
        $s['suggestions'] = $suggestions !== [] ? $suggestions : $base['suggestions'];
        if (! empty($d['topics_present'])) {
            $parsed = self::topicsFrom($d['topics'] ?? []);
            $s['topics'] = $parsed !== [] ? $parsed : $base['topics'];
        }
        SettingsStore::set(self::KEY, $s);

        return self::get();
    }

    public static function reset(): array
    {
        SettingsStore::set(self::KEY, self::defaults());

        return self::get();
    }

    /** @return array<string, mixed> */
    public static function widget(): array
    {
        $s = self::get();

        return [
            'enabled' => $s['enabled'],
            'corner' => $s['corner'],
            'button' => $s['button'],
            'title' => $s['title'],
            'subtitle' => $s['subtitle'],
            'placeholder' => $s['placeholder'],
            'greeting' => $s['greeting'],
            'suggestions' => $s['suggestions'],
            'avatar' => $s['avatar'],
            'show_sales' => $s['show_sales'],
            'sales_label' => $s['sales_label'],
            'sales_url' => $s['sales_url'],
            'show_support' => $s['show_support'],
            'support_label' => $s['support_label'],
            'support_url' => $s['support_url'],
            'consult_label' => $s['consult_label'],
            'consult_phone' => $s['consult_phone'],
            'consult_url' => 'tel:'.$s['consult_phone'],
        ];
    }

    public static function greeting(): string
    {
        return (string) self::get()['greeting'];
    }

    /** @return list<string> */
    public static function suggestions(): array
    {
        return self::get()['suggestions'];
    }

    /**
     * @return array{text:string,links:list<array{label:string,url:string}>,handoff:?string}
     */
    public static function reply(string $message): array
    {
        $s = self::get();
        $q = self::norm($message);
        if ($q === '') {
            return self::withConsult(self::pack((string) $s['greeting'], [], null));
        }

        $best = null;
        $bestScore = 0;
        foreach ($s['topics'] as $topic) {
            $score = 0;
            foreach ($topic['keys'] as $key) {
                if (str_contains($q, self::norm($key))) {
                    $score += mb_strlen($key);
                }
            }
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $topic;
            }
        }

        if ($best === null || $bestScore < 2) {
            return self::withConsult(self::pack((string) $s['fallback'], [
                ['label' => 'تماس', 'url' => '/contact'],
                ['label' => 'طراحی سایت', 'url' => '/design'],
                ['label' => 'حسابداری HDD Land', 'url' => '/computer-accounting'],
            ], null));
        }

        return self::withConsult(self::pack($best['text'], $best['links'], $best['handoff']));
    }

    /**
     * @param  array{text:string,links:list<array{label:string,url:string}>,handoff:?string}  $packed
     * @return array{text:string,links:list<array{label:string,url:string}>,handoff:?string}
     */
    protected static function withConsult(array $packed): array
    {
        if (($packed['handoff'] ?? null) !== 'sales') {
            return $packed;
        }
        $phone = self::phone((string) (self::get()['consult_phone'] ?? ''));
        if ($phone === '') {
            return $packed;
        }
        $shown = self::persianDigits($phone);
        if (! str_contains($packed['text'], $phone) && ! str_contains($packed['text'], $shown)) {
            $packed['text'] .= ' مشاوره فروش: '.$shown.'.';
        }
        $tel = 'tel:'.$phone;
        foreach ($packed['links'] as $link) {
            if (($link['url'] ?? '') === $tel) {
                return $packed;
            }
        }
        $packed['links'][] = ['label' => 'مشاوره فروش '.$shown, 'url' => $tel];

        return $packed;
    }

    /**
     * @param  list<array{label:string,url:string}>  $links
     * @return array{text:string,links:list<array{label:string,url:string}>,handoff:?string}
     */
    protected static function pack(string $text, array $links, ?string $handoff): array
    {
        return ['text' => $text, 'links' => $links, 'handoff' => $handoff];
    }

    protected static function norm(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['ي', 'ك', 'ة', 'أ', 'إ', 'ؤ', 'ئ', '‌'], ['ی', 'ک', 'ه', 'ا', 'ا', 'و', 'ی', ''], $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    public static function safeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || preg_match('/[\s<>"\']/', $url)) {
            return '';
        }
        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return mb_substr($url, 0, 300);
        }
        if (preg_match('#^https://#i', $url)) {
            return mb_substr($url, 0, 300);
        }
        if (preg_match('#^tel:\+?[0-9]{8,15}$#', $url)) {
            return $url;
        }

        return '';
    }

    public static function phone(string $value): string
    {
        $digits = preg_replace('/\D+/', '', self::latinDigits($value)) ?? '';
        if (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0'.substr($digits, 2);
        }
        if (preg_match('/^0\d{10}$/', $digits)) {
            return $digits;
        }

        return '';
    }

    public static function persianDigits(string $value): string
    {
        return strtr(self::latinDigits($value), ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']);
    }

    protected static function latinDigits(string $value): string
    {
        return strtr($value, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }

    public static function safeAvatar(string $path): string
    {
        $path = ltrim(str_replace('\\', '/', trim($path)), '/');
        if (preg_match('#^images/chat/[A-Za-z0-9._-]+$#', $path)) {
            return $path;
        }

        return 'images/chat/guide.jpg';
    }

    /** @return list<array{keys:list<string>,text:string,links:list<array{label:string,url:string}>,handoff:?string}> */
    public static function topicsFrom(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $keysRaw = $row['keys'] ?? [];
            $keys = is_array($keysRaw) ? $keysRaw : (preg_split('/[,،\n]+/u', (string) $keysRaw) ?: []);
            $cleanKeys = [];
            foreach ($keys as $key) {
                $key = mb_substr(trim((string) $key), 0, 40);
                if ($key !== '') {
                    $cleanKeys[] = $key;
                }
                if (count($cleanKeys) >= 12) {
                    break;
                }
            }
            $text = mb_substr(trim((string) ($row['text'] ?? '')), 0, 800);
            if ($cleanKeys === [] || $text === '') {
                continue;
            }
            $links = [];
            $linksRaw = $row['links'] ?? [];
            if (is_string($linksRaw)) {
                foreach (preg_split('/\n/u', $linksRaw) ?: [] as $line) {
                    $line = trim($line);
                    if ($line === '' || ! str_contains($line, '|')) {
                        continue;
                    }
                    [$label, $url] = array_pad(explode('|', $line, 2), 2, '');
                    $label = mb_substr(trim($label), 0, 40);
                    $url = self::safeUrl(trim($url));
                    if ($label !== '' && $url !== '') {
                        $links[] = ['label' => $label, 'url' => $url];
                    }
                }
            } else {
                foreach ((array) $linksRaw as $link) {
                    if (! is_array($link)) {
                        continue;
                    }
                    $label = mb_substr(trim((string) ($link['label'] ?? '')), 0, 40);
                    $url = self::safeUrl((string) ($link['url'] ?? ''));
                    if ($label !== '' && $url !== '') {
                        $links[] = ['label' => $label, 'url' => $url];
                    }
                }
            }
            $handoff = (string) ($row['handoff'] ?? '');
            $out[] = [
                'keys' => $cleanKeys,
                'text' => $text,
                'links' => array_slice($links, 0, 6),
                'handoff' => in_array($handoff, ['sales', 'support'], true) ? $handoff : null,
            ];
            if (count($out) >= 20) {
                break;
            }
        }

        return $out;
    }

    /** @return list<array{keys:list<string>,text:string,links:list<array{label:string,url:string}>,handoff:?string}> */
    protected static function defaultTopics(string $greeting): array
    {
        $phone = '01144447220';
        $mobile = '09113100473';
        $wa = 'https://wa.me/989113100473';

        return [
            [
                'keys' => ['قیمت', 'هزینه', 'چقدر', 'تعرفه', 'خرید'],
                'text' => 'قیمت طراحی و نرم‌افزار تا وقتی در صفحهٔ همان محصول ثبت نشود اعلام نمی‌شود. برای استعلام، درخواست را به فروش بسپارید تا همان نسخه را بررسی کنند.',
                'links' => [
                    ['label' => 'تماس با فروش', 'url' => '/contact'],
                    ['label' => 'نسخه‌های حسابداری', 'url' => '/computer-accounting'],
                ],
                'handoff' => 'sales',
            ],
            [
                'keys' => ['پشتیبانی', 'تیکت', 'خراب', 'خطا', 'هک', 'سایت بالا نمیاد', 'نگهداری'],
                'text' => 'پشتیبانی سایت شامل تیکت، واتس‌اپ و تلفن دفتر است. اگر سایت خطا می‌دهد یا احتمال هک هست، همان را در درخواست بنویسید. زمان پاسخ وقتی در قرارداد خالی باشد، «در قرارداد» می‌ماند.',
                'links' => [
                    ['label' => 'صفحهٔ پشتیبانی', 'url' => '/design/support'],
                    ['label' => 'تماس', 'url' => '/contact'],
                ],
                'handoff' => 'support',
            ],
            [
                'keys' => ['فروش', 'مشاور', 'رضانژاد', 'واتس', 'واتساپ', 'تلفن', 'تماس', 'شماره'],
                'text' => 'مشاور فروش مهندس رضانژاد است. موبایل '.$mobile.' و تلفن دفتر '.$phone.'. واتس‌اپ هم برای همان شماره باز است.',
                'links' => [
                    ['label' => 'واتس‌اپ فروش', 'url' => $wa],
                    ['label' => 'فرم تماس', 'url' => '/contact'],
                ],
                'handoff' => 'sales',
            ],
            [
                'keys' => ['طراحی سایت', 'طراحی', 'شرکتی', 'فروشگاه اینترنتی', 'لاراول', 'ui', 'سئو', 'رابط'],
                'text' => 'طراحی سایت فراز نت آمل این خدمت‌ها را دارد: سایت شرکتی، فروشگاه اینترنتی، سامانهٔ اختصاصی با لاراول، طراحی UI/UX، سئو و سرعت، و پشتیبانی. هر کدام صفحهٔ جدا دارد و قیمتش تا ثبت شدن خالی است.',
                'links' => [
                    ['label' => 'طراحی سایت', 'url' => '/design'],
                    ['label' => 'سایت شرکتی', 'url' => '/design/corporate'],
                    ['label' => 'فروشگاه', 'url' => '/design/shop'],
                    ['label' => 'لاراول', 'url' => '/design/laravel'],
                    ['label' => 'UI/UX', 'url' => '/design/ui-ux'],
                    ['label' => 'سئو', 'url' => '/design/seo'],
                ],
                'handoff' => 'sales',
            ],
            [
                'keys' => ['حسابداری', 'hdd', 'هارد', 'صنف', 'سوپر', 'رستوران', 'شرکتی حساب', 'کامپیوتر'],
                'text' => 'حسابداری HDD Land نرم‌افزار روی کامپیوتر شماست، با SQL Server 2022 و نسخه‌های صنفی مثل شرکتی، فروشگاهی، سوپرمارکت، پخش، رستوران، کافی‌شاپ، تولید، پیمانکاری و خدماتی. نام محصول «حسابداری HDD Land» است.',
                'links' => [
                    ['label' => 'حسابداری کامپیوتر', 'url' => '/computer-accounting'],
                    ['label' => 'حسابداری وب تعمیرگاه', 'url' => '/accounting'],
                ],
                'handoff' => 'sales',
            ],
            [
                'keys' => ['هوش مصنوعی', 'هوش', 'دستیار', 'آفلاین', 'چت داخل'],
                'text' => 'هوش مصنوعی داخل حسابداری HDD Land روی همان کامپیوتر و بدون اینترنتِ عمومی کار می‌کند. گفتگوی صوتی فارسی و پیشنهاد سند فقط بعد از تأیید حسابدار ثبت می‌شود. این چت سایت جدا از هوش مصنوعی داخل نرم‌افزار است.',
                'links' => [
                    ['label' => 'صفحهٔ هوش مصنوعی', 'url' => '/ai'],
                ],
                'handoff' => 'sales',
            ],
            [
                'keys' => ['پزشک', 'زیبایی', 'دندان', 'روانشناس', 'فیزیو', 'مطب'],
                'text' => 'نرم‌افزار پزشکی برای کلینیک زیبایی، فیزیوتراپی، روانشناسی و دندانپزشکی صفحهٔ جدا دارد. قیمت تا وقتی روی همان صفحه ثبت نشود اعلام نمی‌شود.',
                'links' => [
                    ['label' => 'زیبایی', 'url' => '/software/medical-beauty'],
                    ['label' => 'فیزیوتراپی', 'url' => '/software/medical-physiotherapy'],
                    ['label' => 'روانشناسی', 'url' => '/software/medical-psychology'],
                    ['label' => 'دندانپزشکی', 'url' => '/software/medical-dental'],
                ],
                'handoff' => 'sales',
            ],
            [
                'keys' => ['نماینده', 'نمایندگی', 'شهرستان', 'کارمند', 'کمیسیون', 'درصد'],
                'text' => 'نماینده از کارتابل خودش فروش می‌زند. درصد سهم یا پیش‌فرض همان نرم‌افزار است یا درصد شخص، و روی فاکتور قفل می‌شود. ورود کارمند از صفحهٔ جداست.',
                'links' => [
                    ['label' => 'ورود کارمند', 'url' => '/auth/staff'],
                    ['label' => 'کارتابل نماینده', 'url' => '/staff/desk'],
                ],
                'handoff' => 'sales',
            ],
            [
                'keys' => ['لایسنس', 'فعالسازی', 'فعال سازی', 'سریال'],
                'text' => 'لایسنس روی همان سیستم فعال می‌شود. اگر فایل درخواست دارید، از تیکت پشتیبانی بفرستید تا لایسنس فعال برگردد.',
                'links' => [
                    ['label' => 'پشتیبانی', 'url' => '/design/support'],
                    ['label' => 'ورود کاربر', 'url' => '/auth/customer'],
                ],
                'handoff' => 'support',
            ],
            [
                'keys' => ['تعمیر', 'تعمیرگاه', 'هارد', 'بازیابی'],
                'text' => 'تعمیرگاه و بازیابی تجهیزات ذخیره‌سازی بخش جدا از طراحی سایت است. پذیرش و کارتابل تعمیرگاه روی سایت هست.',
                'links' => [
                    ['label' => 'تعمیرگاه', 'url' => '/repair-shop'],
                ],
                'handoff' => 'support',
            ],
            [
                'keys' => ['درباره', 'فراز', 'امل', 'کی هستید', 'شرکت'],
                'text' => 'شرکت فراز نت آمل (سرزمین هارد) طراحی سایت، نرم‌افزار حسابداری HDD Land و هوش مصنوعی داخل همان نرم‌افزار را می‌سازد. سال‌ها بازیابی اطلاعات و تعمیر تجهیزات ذخیره‌سازی هم انجام داده است.',
                'links' => [
                    ['label' => 'درباره ما', 'url' => '/about'],
                ],
                'handoff' => null,
            ],
            [
                'keys' => ['سلام', 'درود', 'وقت بخیر', 'hello', 'hi'],
                'text' => $greeting,
                'links' => [],
                'handoff' => null,
            ],
        ];
    }
}
