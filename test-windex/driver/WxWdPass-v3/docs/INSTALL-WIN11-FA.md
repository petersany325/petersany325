# نصب درایور — Windows 11 (WxWdPass v3)

## روش صحیح (Win11 x64 + AHCI)

1. **BIOS** → حالت **AHCI** (نه IDE Legacy)
2. هارد DUT روی پورت **غیر از دیسک ویندوز**
3. **Run as Administrator:**

```
D:\test windex\INSTALL-DRIVER.bat
```

یا مستقیم:

```
D:\test windex\driver\WxWdPass-v3\install\INSTALL-WIN11.bat
```

4. چک:

```
CHECK-DRIVER.bat
```

5. اسکن دیسک:

```powershell
powershell -File D:\test windex\driver\WxWdPass-v3\service\WxWdIoService.ps1 -Action Scan
```

6. قفل DUT (مثال دیسک 1):

```powershell
powershell -File ...\WxWdIoService.ps1 -Action Lock -DiskNumber 1
```

## فازها

| فاز | وضعیت | کار |
|-----|--------|-----|
| **Phase 1** | ✅ الان | شناسایی AHCI، محافظت boot، offline دیسک DUT |
| **Phase 2** | ⏳ | `WxWdPassAhci.sys` — قفل کامل پورت + IOCTL |

## WdHd قدیمی

`driver\WdHdSetup\` — **فقط Win7 32-bit** — روی Win11 استفاده نکنید.

## مستندات

`driver\WxWdPass-v3\docs\WXWD-PASS-v3-SPEC.md`
