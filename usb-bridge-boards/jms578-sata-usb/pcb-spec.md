# PCB specification — JMS578 SATA→USB stick

## Mechanical

| Item | Spec |
|------|------|
| Outline | مستطیل **۸۵ × ۲۲ mm** (±۰٫۲) |
| Thickness | **۱٫۲ mm** (ترجیحی برای کنترل امپدانس JLCPCB) |
| Layers | **۴** |
| Finish | ENIG یا HASL سربی/بدون سرب |
| Min track/space | ۰٫۱ / ۰٫۱ mm (۴/۴ mil) کافی است؛ دیفرانسیل را با ماشین‌حساب JLCPCB بزنید |
| Via | ۰٫۳ mm hole / ۰٫۵ mm pad حداقل |
| USB plug | از لبه چپ بیرون بزند یا mid-mount |
| SATA | لبه راست، right-angle مادگی رو به بالا/بیرون |
| سوئیچ + جک DC | کنار USB، مثل استیک‌های فکتوری |

### Floorplan (چپ → راست)

```
[ USB-A Male ] [ SW ] [ DC ] [ U1 JMS578 + Flash + XTAL ] [ SATA 22P ]
     0–15mm      15–28mm              28–60mm                  60–85mm
```

جزئیات بصری: [`floorplan.svg`](floorplan.svg)

## Stackup پیشنهادی (JLCPCB)

استفاده از امپدانس کنترل‌شده JLCPCB، مثلاً:

- **JLC04161H-3313** (۱٫۶mm) یا **JLC0412H-3313** (۱٫۲mm)
- Outer: سیگنال (Top) / سیگنال (Bottom)
- Inner1: **GND** یکپارچه
- Inner2: **۵V** + پر کردن GND

برای دیفرانسیل:

| Interface | Zdiff target | Layer | Tip |
|-----------|--------------|-------|-----|
| USB2 DP/DM | ۹۰Ω | Top | کوتاه، از کریستال دور |
| USB3 SS | ۹۰Ω | Top | طول‌مچ < ۵ mil؛ کپ TX نزدیک چیپ |
| SATA | ۱۰۰Ω | Top | کپ ۱۰nF نزدیک کانکتور؛ reference GND پیوسته |

عرض/فاصله جفت را با [JLCPCB Impedance Calculator](https://cart.jlcpcb.com/impedanceCalculator) برای همان استک‌آپ حساب کنید — عدد ثابت اینجا نگذارید چون به ضخامت مس و core بستگی دارد.

## Placement rules

1. **U1** وسط-چپ؛ پد حرارتی با via به Inner GND.
2. **L1 + C_1V2** بلافاصله کنار پین‌های buck؛ مسیر LX کوتاه و پهن؛ دور از Y1 و REXT.
3. **Y1** نزدیک پین‌های کلاک؛ بدون مسیر پرسرعت از زیر.
4. **U2 Flash** نزدیک GPIO0–3؛ مسیر SPI کوتاه.
5. **کپ‌های SATA ۱۰nF** روی لایه Top، حداکثر ۲–۳mm از پین کانکتور.
6. **کپ‌های USB3 ۱۰۰nF** نزدیک SSTX چیپ.
7. **ESD** در ۱–۲mm کانکتور USB.
8. صفحه GND زیر تمام جفت‌های دیفرانسیل بدون شکاف؛ اگر via عوض لایه شد، viaهای GND در دو طرف جفت بگذارید.

## Polygon / pour

- Top: ریخته‌گری GND اطراف سیگنال‌ها (با clearance به جفت دیف).
- Inner1: GND solid.
- Inner2: V5_SW pour + GND.
- Bottom: GND؛ قطعات ترجیحاً همه Top برای استنسیل تک‌طرفه.

## Fab outputs to generate in KiCad/EasyEDA

وقتی شماتیک را در CAD پیاده کردید، این‌ها را اکسپورت کنید:

- Gerber (GTL/GBL/G2/G3 + GTO/GBO + GTS/GBS + GKO)
- Drill Excellon
- BOM + CPL (برای اسمبلی JLCPCB در صورت نیاز)
- Pick-and-place با مبدا گوشه پایین-چپ

## Assembly notes

1. اول اتصالات برق را با اهم‌متر چک کنید (۵V/۳٫۳V/GND بدون کوتاه).
2. Flash را با پروگرمر SPI پروگرام کنید (فریمور JMS578 معتبر).
3. SSD/HDD ۲٫۵" وصل → USB به هاست USB3 → سوئیچ ON.
4. اگر فقط USB2 دیده شد: جفت SS یا امپدانس/کپ را بررسی کنید.
5. اگر دیسک دیده نشد: جهت TX/RX SATA و وجود دیسک هنگام بوت را چک کنید.

## CAD import path (پیشنهادی)

این ریپو شماتیک متنی و مشخصات می‌دهد. برای Gerber نهایی:

1. EasyEDA / LCEDA: از طرح‌های باز JMS578 روی OSHWHUB به‌عنوان پایه استفاده کنید، سپس مکانیک استیک (USB-A + DC + SW) را مثل `floorplan.svg` جلو ببرید.
2. یا KiCad 8: سمبل QFN48 بسازید، نت‌لیست `nets.csv` را پیاده کنید، استک‌آپ بالا را ست کنید.

لینک‌های پایهٔ تأییدشدهٔ جامعه:

- https://oshwhub.com/valar_morghulisd/ji-yu-jms578-de-usb-zhuan-sata-de-ying-pan-he
- https://oshwhub.com/aknice/jms578-demo
