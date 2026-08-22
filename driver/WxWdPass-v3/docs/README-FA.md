# درایور Windex WD — خلاصه فارسی

## وضعیت فعلی

| نسخه | سیستم | وضعیت |
|------|--------|--------|
| **WdHd 2.x** (WD قدیمی) | Win7 **32-bit** + BIOS IDE | ✅ در repo — فقط لاب |
| **WxWdPass 3.0** (HamGap) | Win10/11 **x64** + BIOS **AHCI** | 📋 spec آماده — کد kernel هنوز build نشده |

## چرا WdHd قدیمی روی PC جدید کار نمی‌کند؟

1. فقط **32-bit** — `WdHdSetup.exe` روی 64-bit خطا می‌دهد
2. فقط **IDE/Compatible** — AHCI در BIOS پشتیبانی نمی‌شود
3. `IOCTL_WDHDD_SATA_COMMAND` در `wdhdgen.sys` → **Unsupported**
4. Secure Boot / امضای Microsoft ندارد

## ساختار نصب قدیمی (32-bit)

```
test-windex/driver/WdHdSetup/
├── WdHdSetup.exe     ← نصب‌کننده GUI
├── wdhdgen.sys       ← IDE generic
├── wdhdpro.sys       ← Promise
├── wdhdsi.sys        ← Silicon Image
└── *.inf
```

**جریان:** اسکن کنترلر → تیک channel غیر-بوت → جایگزینی driver → HDD از Windows مخفی → Windex با IOCTL

## نسخه جدید پیشنهادی: WxWdPass v3.0

### قابلیت‌ها

- تشخیص HDD روی پورت AHCI (Identify)
- قفل exclusive پورت (`IOCTL_WXWD_LOCK_PORT`)
- ATA + VSC برای تعمیر
- Win10/11 x64
- چک لایسنس داخل driver

### ساختار نصب جدید

```
driver/WxWdPass-v3/
├── docs/WXWD-PASS-v3-SPEC.md    ← مشخصات کامل
├── include/wxwd_ioctl.h         ← API
├── install/INSTALL-AHCI.bat
└── install/inf/wxwdahci.inf     ← template
```

### فازهای پیاده‌سازی

| فاز | کار |
|-----|-----|
| 0 | spec + IOCTL (✅ الان) |
| 1 | WxWdIoService — ATA pass-through بدون kernel |
| 2 | WxWdPassAhci.sys — قفل واقعی پورت |
| 3 | WxWdPassSetup.exe + WHQL |
| 4 | اتصال UI Windex WD به IOCTL واقعی |

## نصب

```bat
test-windex\INSTALL-DRIVER.bat
```

گزینه 1 = AHCI جدید | گزینه 2 = WdHd قدیمی (لاب 32-bit)

## مستندات انگلیسی (جزئیات فنی)

- `driver/WxWdPass-v3/docs/LEGACY-WDHDD-32BIT.md`
- `driver/WxWdPass-v3/docs/WXWD-PASS-v3-SPEC.md`
