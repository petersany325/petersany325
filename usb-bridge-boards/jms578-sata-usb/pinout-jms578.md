# JMS578 Pin Map (QFN-48, 6×6 mm)

مرجع: datasheet JMicron JMS578 (PDS-16002). شماره‌گذاری برای اتصال شماتیک این پروژه.

## SATA

| Pin | Name | Net | Note |
|-----|------|-----|------|
| 30 | RXP | SATA_RX_P | از کانکتور SATA A+؛ سری ۱۰nF |
| 31 | RXN | SATA_RX_N | از کانکتور SATA A−؛ سری ۱۰nF |
| 34 | TXP | SATA_TX_P | به کانکتور SATA B+؛ سری ۱۰nF |
| 33 | TXN | SATA_TX_N | به کانکتور SATA B−؛ سری ۱۰nF |
| 35 | NC | 3V3 | در بسیاری از کیس‌های تجاری به ۳٫۳V وصل است |
| 36 | REXT | REXT | مقاومت ۱۲kΩ ±۱٪ به GND |
| 32 | AVDDL | 1V2_SATA | آنالوگ ۱٫۲V SATA |

## USB 3.0 SuperSpeed

| Pin | Name | Net | Note |
|-----|------|-----|------|
| 23 | SSRXP | USB_SSRX_P | از Host SSTX؛ بدون کپ روی Device RX |
| 22 | SSRXN | USB_SSRX_N | |
| 20 | SSTXP | USB_SSTX_P | به Host SSRX؛ سری ~۱۰۰nF نزدیک چیپ |
| 19 | SSTXN | USB_SSTX_N | سری ~۱۰۰nF |

## USB 2.0

| Pin | Name | Net |
|-----|------|-----|
| 16 | DP | USB_DP |
| 15 | DM | USB_DM |

## Clock

| Pin | Name | Net | Note |
|-----|------|-----|------|
| — | XI / XO | XTAL_30M | کریستال ۳۰ MHz + ۲× load cap (~۱۲–۱۸pF) |

شماره دقیق XI/XO را از datasheet همان ریویژن بچسبانید (معمولاً نزدیک پین‌های کلاک آنالوگ).

## SPI Flash (GPIO0–3)

| Function | Typical GPIO | Flash Pin |
|----------|--------------|-----------|
| CS# | GPIO0 | /CS |
| SCK | GPIO1 | CLK |
| MOSI | GPIO2 | DI |
| MISO | GPIO3 | DO |

Flash: ۳٫۳V SPI NOR، ترجیحاً ۵۱۲ Kbit (مثلاً PM25LV512 / مشابه Winbond).

## Power / Misc

| Pin group | Net | Note |
|-----------|-----|------|
| VBUS / 5V pins | V5_SW | بعد از سوئیچ |
| AV33O | 3V3 | خروجی LDO داخلی (~۱۰۰mA) — فقط چیپ + Flash + LED |
| LXO / LX buck | 1V2 via L1=4.7µH | Cout ۱۰–۲۰µF نزدیک چیپ |
| GPIO4 | LED_ACT | LED فعالیت (آند به ۳٫۳V، کاتد با مقاومت به GPIO) |
| GND paddle | GND | پد وسط QFN را با چند via به صفحه GND وصل کنید |

## USB-A Male (Type-A plug) — نیت‌های برد

| USB-A | Net |
|-------|-----|
| VBUS | V5_USB |
| D− | USB_DM |
| D+ | USB_DP |
| GND | GND |
| SSRX− / SSRX+ | به SSTX چیپ (کراس در کابل/پین استاندارد Type-A) |
| SSTX− / SSTX+ | به SSRX چیپ |
| GND_DRAIN | GND |

روی PCB Type-A نر، جفت SuperSpeed را طبق پین‌اوت کانکتور انتخابی مسیریابی کنید؛ P/N را فقط برای USB3 می‌توانید عوض کنید، نه USB2.
