# Land-TestFTD232 — گزینه تشخیص اصل / تقلبی

## وضعیت
سورس کامل Land-TestFTD232 (v7.4) در این ورک‌اسپیس موجود نیست.
ماژول آماده: `authenticity_check.py`

## خروجی‌ها
| کد | فارسی |
|---|---|
| `GENUINE_LIKELY` | آی‌سی احتمالاً اصلی |
| `COUNTERFEIT_SUSPECT` | آی‌سی مشکوک به تقلبی |
| `INCONCLUSIVE` | نامشخص |

## اتصال به GUI موجود
1. فایل `authenticity_check.py` را کنار سورس اصلی بگذارید.
2. یک چک‌باکس اضافه کنید: **«تشخیص اصل/تقلبی آی‌سی»** (پیش‌فرض روشن).
3. بعد از `EEPROM read` و (در صورت فعال بودن) TX↔RX loopback، این را صدا بزنید:

```python
from authenticity_check import run_authenticity_check, format_authenticity_report

extras = {
    "loopback_ok_38400": lb_38400_pass,      # یا None اگر اجرا نشده
    "loopback_ok_460800": lb_460800_pass,
    "loopback_ok_921600": lb_921600_pass,
    "eslip_ok_460800": eslip_pass,
    "boot_banner_ok": boot_pass,
}
result = run_authenticity_check(
    device_info={"description": desc, "serial": sn, "id": usb_id, "type": dtype},
    eeprom_words=eeprom_64_words,
    extras=extras,
)
report_text += "\n" + format_authenticity_report(result)
# و روی UI برچسب بزرگ: result["fa_label"]
```

4. برای دقت بیشتر، چک‌باکس **TX↔RX loopback** را هنگام تست اصالت روشن کنید.

## محدودیت
هیچ نرم‌افزاری ۱۰۰٪ اصل بودن FT232 را ضمانت نمی‌کند.
این ماژول بر اساس VID/PID، سریال، ساختار EEPROM و پایداری baud قضاوت می‌کند.

## کار بعدی
سورس/ZIP نسخه فعلی را آپلود کنید تا چک‌باکس و برچسب فارسی مستقیم داخل خود برنامه ادغام شود.
