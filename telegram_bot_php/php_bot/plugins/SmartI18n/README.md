# SmartI18n Plugin

Intelligent language layer for HDD-Land bot.

## What it does
1. On `/start` → user picks language
2. Plugin auto-translates **all menus & submenus** to that language
3. English source menus in DB stay untouched (translations go to `menu_i18n` + `i18n_cache`)
4. Shows a smart hub with all categories visible after language select
5. Dictionary for instant FA/RU/ZH + optional OpenAI for missing phrases

## Files (plugin only)
```
php_bot/plugins/loader.php
php_bot/plugins/SmartI18n/plugin.php
php_bot/plugins/SmartI18n/dictionary.php
```

## Disable plugin
Rename or delete folder:
`php_bot/plugins/SmartI18n`
