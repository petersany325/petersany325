<?php
declare(strict_types=1);

final class Keyboards
{
    public static function mainInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '🎲 چت تصادفی', 'callback_data' => 'chat:any'],
                ['text' => '💬 چت هوشمند', 'callback_data' => 'menu:smart'],
            ],
            [
                ['text' => '👤 پروفایل', 'callback_data' => 'menu:profile'],
                ['text' => '💎 کیف سکه', 'callback_data' => 'menu:wallet'],
            ],
            [
                ['text' => 'ℹ️ راهنما', 'callback_data' => 'menu:help'],
                ['text' => '🆘 پشتیبانی', 'callback_data' => 'menu:support'],
            ],
        ]];
    }

    public static function mainReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '🎲 چت تصادفی'], ['text' => '💬 چت هوشمند']],
                [['text' => '👤 پروفایل'], ['text' => '💎 کیف سکه']],
            ],
            'resize_keyboard' => true,
        ];
    }

    public static function smartInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '👨 پسر · ۱ سکه', 'callback_data' => 'chat:male'],
                ['text' => '👩 دختر · ۱ سکه', 'callback_data' => 'chat:female'],
            ],
            [
                ['text' => '🔙 بازگشت', 'callback_data' => 'menu:main'],
            ],
        ]];
    }

    public static function gender(): array
    {
        return ['inline_keyboard' => [[
            ['text' => '👨 پسر', 'callback_data' => 'reg:gender:male'],
            ['text' => '👩 دختر', 'callback_data' => 'reg:gender:female'],
        ]]];
    }

    public static function searching(): array
    {
        return ['inline_keyboard' => [[
            ['text' => '❌ لغو جستجو', 'callback_data' => 'chat:cancel'],
        ]]];
    }

    public static function chattingInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '⏭ بعدی', 'callback_data' => 'chat:next'],
                ['text' => '🛑 پایان', 'callback_data' => 'chat:end'],
            ],
            [
                ['text' => '🚩 گزارش', 'callback_data' => 'chat:report'],
            ],
        ]];
    }

    public static function chattingReply(): array
    {
        return [
            'keyboard' => [
                [['text' => '⏭ بعدی'], ['text' => '🛑 پایان چت']],
                [['text' => '🚩 گزارش']],
            ],
            'resize_keyboard' => true,
        ];
    }

    public static function profileInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '📝 ویرایش پروفایل', 'callback_data' => 'menu:edit_profile'],
            ],
            [
                ['text' => '🔙 بازگشت', 'callback_data' => 'menu:main'],
            ],
        ]];
    }

    public static function editProfileInline(): array
    {
        return ['inline_keyboard' => [
            [
                ['text' => '✏️ جنسیت', 'callback_data' => 'edit:gender'],
                ['text' => '✏️ سن', 'callback_data' => 'edit:age'],
            ],
            [
                ['text' => '✏️ شهر', 'callback_data' => 'edit:city'],
                ['text' => '🔙 پروفایل', 'callback_data' => 'menu:profile'],
            ],
        ]];
    }

    public static function walletInline(): array
    {
        return ['inline_keyboard' => [
            [['text' => '۱۰ سکه — ۲۰٬۰۰۰ تومان', 'callback_data' => 'pay:soon']],
            [['text' => '۳۰ سکه — ۵۰٬۰۰۰ تومان ★', 'callback_data' => 'pay:soon']],
            [['text' => '۱۰۰ سکه — ۱۴۰٬۰۰۰ تومان', 'callback_data' => 'pay:soon']],
            [['text' => '🔙 بازگشت', 'callback_data' => 'menu:main']],
        ]];
    }
}
