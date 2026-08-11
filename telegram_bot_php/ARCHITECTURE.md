# HDD-Land Telegram Bot — Professional Architecture

## Goal
Move from a monolithic `webhook.php` to a layered bot that scales like production Telegram bots (aiogram/grammY style), while staying compatible with cPanel PHP + existing admin panel.

## Layers

```
Telegram Update
      │
      ▼
webhook.php                 # thin HTTP entry only
      │
      ▼
BotKernel                   # secret check + dispatch
      │
      ▼
Middleware Pipeline         # EnsureUser → Maintenance
      │
      ▼
Handlers (Routers)          # MessageRouter / CallbackRouter
      │
      ▼
Services                    # Shop / Ticket / FAQ / Forum / AI / Content
      │
      ▼
Repositories                # DB access only
```

## Directory map

```text
php_bot/
├── webhook.php                 # entry (thin)
├── webhook.legacy.php          # pre-refactor backup
├── bootstrap.php               # shared TG helpers + config/db (used by admin too)
├── menu_faq.php / requests.php # existing domain helpers (gradually movable)
├── admin/                      # web control panel (unchanged contract)
└── src/
    ├── Autoload.php
    ├── BotKernel.php
    ├── Context.php
    ├── Middleware/
    ├── Handlers/
    ├── Services/
    ├── Repositories/
    └── Support/
```

## Rules
1. **Handlers** only route + call services + send UI.
2. **Services** own business rules (tickets, shop, AI…).
3. **Repositories** own SQL/CRUD.
4. **Middleware** owns cross-cutting concerns (user ensure, maintenance).
5. Admin panel keeps using `bootstrap.php` helpers — no break.

## Rollback
If needed, rename on host:
```bash
cp webhook.legacy.php webhook.php
```

## Next stages (optional)
1. Move Pro Desk (`requests.php`) into `Services/RequestDeskService`.
2. Move menu/FAQ builders into repositories/services.
3. Add Redis queue worker for broadcast / AI.
4. Add PHPUnit tests for services.
