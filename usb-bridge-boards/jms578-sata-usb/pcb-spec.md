# PCB specification — JMS578 SATA to USB stick (2-layer)

## Mechanical

| Item | Spec |
|------|------|
| Outline | مستطیل **۸۵ × ۲۲ mm** (±۰٫۲) |
| Thickness | **۱٫۶ mm** استاندارد JLCPCB دو لایه (یا ۱٫۲ mm اگر خواستی نازک‌تر) |
| Layers | **۲** (Top سیگنال / Bottom GND) |
| Finish | HASL Lead-Free یا ENIG |
| Copper | ۱ oz (یا Outer 1oz) |
| Min track/space | ۰٫۱ / ۰٫۱ mm |
| Via | ۰٫۳ mm hole / ۰٫۵ mm pad |
| USB plug | لبه چپ، Type-A male |
| SATA | لبه راست، 22-pin مادگی |
| **SW1 ON/OFF** | راکر کنار USB؛ سیلک واضح `ON` / `OFF` |
| جک DC | کنار سوئیچ، ۵V کمکی |

### Floorplan (چپ به راست)

```
[ USB-A Male ] [ ON/OFF ] [ DC 5V ] [ U1 + Flash + XTAL + L1 ] [ SATA 22P ]
     0-15mm      15-28mm    28-38mm           38-62mm              62-85mm
```

جزئیات: [`floorplan.svg`](floorplan.svg) / [`floorplan.png`](floorplan.png)

## چرا ۲ لایه قابل قبول است

دیتاشیت/مارکتینگ JMS578 صریحاً طراحی **دو لایه** ارزان را هدف می‌گیرد. خیلی از استیک‌های بازار هم ۲ لایه‌اند. هزینه و ساخت ساده‌تر از ۴ لایه است؛ در عوض امپدانس USB3 کمی حساس‌تر است — جفت‌ها را کوتاه نگه دارید.

## Stackup دو لایه (JLCPCB)

| Layer | نقش |
|-------|-----|
| **Top (L1)** | قطعات + جفت‌های USB/SATA + مسیر ۵V پهن |
| **Bottom (L2)** | **GND solid** تقریباً کامل؛ زیر جفت‌های دیف شکاف نزنید |

برای دیفرانسیل روی ۲ لایه (میکرواستریپ با رفرنس Bottom GND):

| Interface | Zdiff | Tip |
|-----------|-------|-----|
| USB2 DP/DM | ۹۰Ω | کوتاه، دور از کریستال و L1 |
| USB3 SS | ۹۰Ω | طول‌مچ جفت کمتر از ۵ mil؛ کپ TX نزدیک چیپ |
| SATA | ۱۰۰Ω | کپ ۱۰nF نزدیک کانکتور |

عرض/گپ را با [JLCPCB Impedance Calculator](https://cart.jlcpcb.com/impedanceCalculator) برای **۲ لایه** و ضخامت انتخابی حساب کنید.

اگر ماشین‌حساب ۲ لایه ۹۰/۱۰۰Ω نداد: جفت را کوتاه‌تر کنید، فاصله به GND pour کناری (coplanar) بگذارید، یا ضخامت ۱٫۲ mm را امتحان کنید.

## کلید ON / OFF

| Ref | Type | Wiring |
|-----|------|--------|
| `SW1` | راکر SPST، جریان ≥ ۳A، سیلک `ON` / `OFF` | قطع می‌کند `V5_OR` → `V5_SW` |

- در وضعیت **OFF**: چیپ و دیسک کاملاً بی‌برق (برای جا زدن امن دیسک / 开卡).
- در وضعیت **ON**: ۵V از USB یا جک DC (بعد از دیود OR) به کل برد و SATA 5V می‌رود.
- سیلک کنار سوئیچ حتماً خوانا باشد: `ON` سمت وصل، `OFF` سمت قطع.

## Placement rules (۲ لایه)

1. **همه قطعات روی Top** (اسمبلی تک‌طرفه).
2. **Bottom فقط GND** + چند jumper برق اگر مجبور شدید (حداقل).
3. **U1** پد وسط با چند via به Bottom GND.
4. **L1 + C_1V2** حلقه کوتاه روی Top؛ از Y1 دور.
5. **جفت USB/SATA** فقط روی Top، زیرشان Bottom GND پیوسته؛ via GND در دو طرف اگر لازم شد.
6. مسیر **V5_SW** پهن (≥ ۱ mm) از SW1 به U1 و به پین ۵V ساتا.
7. کپ‌های SATA ۱۰nF نزدیک کانکتور؛ کپ USB3 نزدیک SSTX.

## Polygon / pour

- Top: ریخته‌گری GND اطراف (clearance به جفت دیف و به V5).
- Bottom: GND یکپارچه؛ استایل حرارتی روی پدهای TH سوئیچ/جک/USB اگر لازم.
- ریخته‌گری ۵V فقط روی Top، پهن و کوتاه.

## Fab outputs

- Gerber دو لایه: `GTL`, `GBL`, `GTO`, `GBO`, `GTS`, `GBS`, `GKO`
- Drill Excellon
- BOM + CPL اختیاری

**نه** لایه Inner (G2/G3) لازم نیست.

## Assembly notes

1. اتصال ۵V/GND را قبل از جا زدن چیپ چک کنید.
2. SW1 را روی OFF بگذارید، دیسک را وصل کنید، USB/DC را بزنید، بعد **ON**.
3. Flash را با SPI پروگرام کنید.
4. اگر فقط USB2 دیدید: جفت SS یا امپدانس ۲ لایه را بازبینی کنید.

## CAD path

1. EasyEDA: فورک طرح JMS578 دو لایه از OSHWHUB، مکانیک استیک + SW ON/OFF + DC را اضافه کنید.
2. KiCad: نت‌لیست `nets.csv`، استک‌آپ ۲ لایه، Bottom = GND.

رفرنس جامعه:

- https://oshwhub.com/valar_morghulisd/ji-yu-jms578-de-usb-zhuan-sata-de-ying-pan-he
- https://oshwhub.com/aknice/jms578-demo
