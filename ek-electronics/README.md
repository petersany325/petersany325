# EK Electronics — English store + operations panel (preview)

Interactive design sample for **www.ekelectronics.co.za** based on the client website outline.

- Public store is fully English (ZAR, South African address, Afrihost domain).
- Palette: royal blue, black, white, grey. Tone: approachable, tech-savvy.
- Tag line: *Innovation. Integrity. Impact.*
- Cart checkout and **all accounting / job / invoice messages go out on WhatsApp**.

## Run locally

```bash
cd ek-electronics
python3 -m http.server 4173
```

Open `http://127.0.0.1:4173/` (shop) and `http://127.0.0.1:4173/admin.html` (staff panel).

## Pages

| Surface | What it covers |
| --- | --- |
| Home | Banner copy from the brief, search / profile / cart, product photos |
| Shop | Category filters from the outline (HDD, SSD, memory, RAID, docks…) |
| Services | Refurbishment, data recovery, secure erasure, add-ons |
| About | Mission, vision, differentiators, story |
| Contact | Lone Creek Unit 15, Midrand map, WhatsApp form |
| Cart / checkout | Line items → structured WhatsApp order |
| Admin | Dashboard, orders, inventory, recovery jobs, P&L, VAT 15%, WhatsApp desk |

Phone in the brief was `+27 xxx xxxxxx`. Preview uses **+27 10 500 2140** until the live number is supplied.

## Checkout options (as requested)

1. **WhatsApp order desk (this preview)** — lowest friction for B2B and recovery quotes.
2. **EFT against WhatsApp invoice** — default for resellers.
3. **Card (PayFast / Peach)** — recommended production add-on; still notify accounts on WhatsApp.
4. **Collect in Midrand** — Unit 15, Lone Creek Office Building D.
