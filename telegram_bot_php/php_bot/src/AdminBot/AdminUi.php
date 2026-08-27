<?php
declare(strict_types=1);

namespace HddLand\Bot\AdminBot;

/**
 * Professional English UI for SeDiv Admin Console bot.
 */
final class AdminUi
{
    public static function gateText(): string
    {
        return "🛡 <b>SeDiv Admin Console</b>\n"
            . "<i>English · Staff only</i>\n\n"
            . "Sign in with your web-panel username and password.\n"
            . "Commands: /login · /logout · /menu · /help";
    }

    public static function mainText(string $username): string
    {
        $site = htmlspecialchars((string)cfg('site_url', 'https://hdd-land.com'));
        $title = htmlspecialchars((string)cfg('bot_title', 'HDD-Land Bot'));
        $maint = !empty(cfg('maintenance_mode', 0)) ? 'ON' : 'OFF';
        return "🛡 <b>SeDiv Admin Console</b>\n"
            . "Signed in as <b>" . htmlspecialchars($username) . "</b>\n\n"
            . "Public bot: <b>{$title}</b>\n"
            . "Site: {$site}\n"
            . "Maintenance: <code>{$maint}</code>\n\n"
            . "Select a module:";
    }

    public static function mainKeyboard(): array
    {
        return array(
            'inline_keyboard' => array(
                array(
                    array('text' => '📊 Dashboard', 'callback_data' => 'adm:dash'),
                    array('text' => '🎫 Tickets', 'callback_data' => 'adm:tickets'),
                ),
                array(
                    array('text' => '🛠 Support & Sales', 'callback_data' => 'adm:requests'),
                    array('text' => '🧩 Ticket Fields', 'callback_data' => 'adm:tfields'),
                ),
                array(
                    array('text' => '❓ FAQ', 'callback_data' => 'adm:faqs'),
                    array('text' => '📋 Menus', 'callback_data' => 'adm:menus'),
                ),
                array(
                    array('text' => '🛒 Products', 'callback_data' => 'adm:products'),
                    array('text' => '📢 Broadcast', 'callback_data' => 'adm:broadcast'),
                ),
                array(
                    array('text' => '🌍 Languages', 'callback_data' => 'adm:langs'),
                    array('text' => '👥 Users', 'callback_data' => 'adm:users'),
                ),
                array(
                    array('text' => '🎛 User Options', 'callback_data' => 'adm:uopt'),
                    array('text' => '💵 Receipts & Licenses', 'callback_data' => 'adm:receipts'),
                ),
                array(
                    array('text' => '🔐 Admins & Access', 'callback_data' => 'adm:admins'),
                    array('text' => '⚙️ Settings', 'callback_data' => 'adm:settings'),
                ),
                array(
                    array('text' => '🏷 Branding', 'callback_data' => 'adm:branding'),
                    array('text' => '❤️ Health & Repair', 'callback_data' => 'adm:health'),
                ),
                array(
                    array('text' => '🌐 Open Web Admin', 'callback_data' => 'adm:weblink'),
                    array('text' => '🚪 Logout', 'callback_data' => 'adm:logout'),
                ),
            ),
        );
    }

    public static function backHome(): array
    {
        return array(
            'inline_keyboard' => array(
                array(array('text' => '⬅️ Main Menu', 'callback_data' => 'adm:home')),
            ),
        );
    }

    public static function kb(array $rows): array
    {
        $rows[] = array(array('text' => '⬅️ Main Menu', 'callback_data' => 'adm:home'));
        return array('inline_keyboard' => $rows);
    }

    public static function helpText(): string
    {
        return "<b>Admin Console — Help</b>\n\n"
            . "/login — Sign in (username + password)\n"
            . "/logout — End session\n"
            . "/menu — Open main modules\n"
            . "/dash — Dashboard stats\n"
            . "/tickets — Open tickets\n"
            . "/receipts — Pending payment receipts\n"
            . "/broadcast — Message all users\n"
            . "/health — System health\n\n"
            . "This bot is English-only and mirrors the web Admin panel modules.";
    }
}
