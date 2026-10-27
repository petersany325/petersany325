# فایل ساخت / چاپ مدار

## `.PcbDoc` چیست؟

`.PcbDoc` فرمت **اختصاصی Altium Designer** برای ویرایش PCB است — باینری/پروژه‌ای، نه فایل استاندارد چاپخانه.

این محیط Altium ندارد؛ بنابراین فایل `.PcbDoc` واقعی تولید نمی‌شود.  
برای **چاپ/ساخت PCB** کارخانه‌ها (JLCPCB، PCBWay، چاپخانه‌های داخلی) معمولاً **Gerber + Drill** می‌خواهند، نه `.PcbDoc`.

| فرمت | کاربرد | اینجا |
|------|--------|--------|
| **Gerber ZIP** | سفارش چاپ مدار | `jms578-sata-usb-gerbers.zip` |
| **KiCad `.kicad_pcb`** | ویرایش CAD رایگان | `kicad/jms578-sata-usb.kicad_pcb` |
| **`.PcbDoc`** | فقط داخل Altium | نیست — اگر لازم داری، Gerber را در Altium Import کن یا پروژه را در Altium از نو بکش |

### اگر حتماً Altium می‌خواهی
1. Altium → File → Import → Gerber (همین ZIP)، یا  
2. پروژه را در Altium از روی `SCHEMATIC.md` + `bom.csv` دوباره بساز و Save as `.PcbDoc`.

## فایل‌های آماده چاپ

مسیر: `usb-bridge-boards/jms578-sata-usb/`

- `jms578-sata-usb-gerbers.zip` — آپلود مستقیم به JLCPCB/PCBWay
- `gerbers/` — لایه‌ها جدا (`.gtl` `.gbl` `.gto` `.gbo` `.gts` `.gbs` `.gm1` + `.drl`)
- `kicad/jms578-sata-usb.kicad_pcb` — برد دو لایه ۸۵×۲۲mm با ON/OFF
- `pcb-preview.svg` — پیش‌نمایش

## بازتولید

```bash
cd usb-bridge-boards/jms578-sata-usb/kicad
python3 build_pcb.py
```

## هشدار فنی
این برد **جانمایی + پاور + GND pour** است. مسیردهی کامل دیفرانسیل USB3/SATA و نت‌های تمام پایه‌های JMS578 باید قبل از اسمبلی نهایی در KiCad/Altium تکمیل و DRC شود. برای نمونه اول فقط PCB خالی (بدون اسمبلی کامل پرسرعت) سفارش بده مگر مسیردهی را تمام کرده باشی.
