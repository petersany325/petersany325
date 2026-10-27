# Schematic — JMS578 SATA→USB Stick

## Block diagram

```
                 ┌─────────────┐
  USB-A Male ────┤ ESD / ferr  ├── USB2 DP/DM ──────────────┐
                 │             ├── USB3 SS TX/RX (+100nF) ──┤
                 │             └── V5_USB                   │
                 └─────────────┘                            │
                        │                                   │
                   DC Jack 5V ── Schottky ──┐               │
                                            ▼               ▼
                                      ┌──────────┐    ┌──────────┐
                                      │ SW I/O   │───►│ V5_SW    │
                                      └──────────┘    └────┬─────┘
                                                           │
                              ┌────────────────────────────┼──────────────────┐
                              │                            │                  │
                              ▼                            ▼                  ▼
                         SATA 5V                     JMS578 5V in        LED/logic
                         (power pin)                 LDO→3V3             via 3V3
                                                     Buck→1V2 (4.7µH)
                                                           │
                              ┌────────────────────────────┤
                              │ SPI Flash 512Kbit          │
                              │ 30MHz XTAL                 │
                              │ REXT 12k                   │
                              │                            │
                              │     SATA TX/RX + 10nF      ▼
                              │                    ┌──────────────┐
                              └───────────────────►│ SATA Data    │──► 2.5" drive
                                                   │ SATA Power   │──► 5V / GND
                                                   └──────────────┘
```

## Sections

### 1) USB connector & protection

- `J1` USB Type-A **male** (plug, mid-mount or upright per مکانیک).
- `V5_USB` → فیوز ریست‌شونده یا PTC ~۱٫۵–۲A اختیاری (`F1`).
- ESD: USBLC6-2 برای D+/D−؛ برای SS در صورت امکان TPD4E05 یا مشابه نزدیک کانکتور.
- فریت روی VBUS نزدیک کانکتور (`FB1` 600Ω@100MHz، جریان ≥۲A).

### 2) Power path

| Ref | Function |
|-----|----------|
| `J2` | جک DC ۵٫۵×۲٫۱، مرکز مثبت، ۵V |
| `D1` | Schottky (مثلاً SS34 / 1N5819) از جک به `V5_OR` تا از برگشت به USB جلوگیری کند |
| `D2` | Schottky از `V5_USB` به `V5_OR` (OR-ing ساده) |
| `SW1` | سوئیچ راکر I/O بین `V5_OR` و `V5_SW` |
| `C_IN` | ۱۰µF + ۱۰۰nF روی `V5_SW` نزدیک چیپ |
| `L1` | ۴٫۷µH برای buck داخلی ۱٫۲V (مسیر کوتاه، از XTAL دور) |
| `C_1V2` | ۱۰–۲۰µF سرامیک روی ۱٫۲V |
| `C_3V3` | ۱۰µF + ۱۰۰nF روی خروجی ۳٫۳V |

`V5_SW` همزمان به:

- پین‌های ۵V چیپ JMS578
- پین ۵V کانکتور SATA Power (یا پین ۱۵ کانکتور ترکیبی)

### 3) JMS578 core

- `U1` JMS578-QG
- `Y1` ۳۰٫۰۰۰ MHz، fundamental، CL مطابق دیتاشیت (~۱۲pF معمول)
- `C_XTAL1/2` ۱۲–۱۸pF به GND
- `R_REXT` ۱۲kΩ ±۱٪ از REXT به GND
- `U2` SPI Flash ۳٫۳V، ۵۱۲ Kbit، سیم‌کشی GPIO0–3
- `R_CS` پول‌آپ ۱۰k روی CS# به ۳٫۳V (در صورت نیاز دیتاشیت Flash)
- پد حرارتی وسط چیپ → GND با ۴–۹ via

### 4) SATA interface

کانکتور پیشنهادی: **SATA 22-pin** (۷ دیتا + ۱۵ پاور) مادگی برای ۲٫۵ اینچ، یا جدا:

- `J3` SATA Data 7-pin
- `J4` SATA Power 15-pin (فقط ۵V و GND استفاده؛ ۳٫۳V و ۱۲V NC)

AC coupling (هر چهار خط، ۱۰nF، ≤۰۴۰۲، نزدیک کانکتور):

| Cap | Net |
|-----|-----|
| C_TXP | U1.TXP — SATA_B+ |
| C_TXN | U1.TXN — SATA_B− |
| C_RXP | SATA_A+ — U1.RXP |
| C_RXN | SATA_A− — U1.RXN |

امپدانس دیفرانسیل هدف: **۱۰۰Ω**. جفت TX و RX را کوتاه و با رفرنس GND کامل نگه دارید.

**توجه جهت:** اگر TX/RX جابه‌جا شود دستگاه دیده نمی‌شود یا خطای عجیب می‌دهد؛ در صورت شک، جفت‌ها را روی PCB یا با سیم‌پرانی عوض کنید.

### 5) Indicators & debug

- `D_ACT` LED سبز/آبی + `R_LED` ۳۳۰Ω–۱k از ۳٫۳V به GPIO4 (فعالیت)
- `D_PWR` LED اختیاری روی `V5_SW` با مقاومت
- پدهای تست: `TP_GND`, `TP_5V`, `TP_3V3`, `TP_1V2`
- هدر ۲٫۵۴ اختیاری ۴ پین برای UART نیست روی JMS578 استاندارد؛ برای Flash خارجی از کلیپ SPI / تست‌پدهای SPI استفاده کنید:

```
TP_SPI:  3V3 | CS | CLK | MOSI | MISO | GND
```

### 6) Mechanical keepouts

- سوراخ‌های پیچ برای نگه‌داشتن کابل/بدنه اختیاری در دو سر برد
- لبه USB-A بیرون از outline به اندازه طول پلاگ (~۱۲–۱۵mm بیرون PCB یا mid-mount)
- کانکتور SATA در انتهای مخالف USB

## Net list (high level)

همه نت‌های اصلی در [`nets.csv`](nets.csv) هستند.

## Design checklist before fab

- [ ] USB3: ۹۰Ω ±۱۰٪، طول جفت داخل جفت < ۵ mil اختلاف
- [ ] SATA: ۱۰۰Ω، کپ ۱۰nF نزدیک کانکتور
- [ ] پدل QFN به GND
- [ ] سلف ۴٫۷µH و مسیر LX از کریستال دور
- [ ] OR-ing دیودها جهت درست
- [ ] فقط ۵V به پاور SATA (۱۲V وصل نشود)
