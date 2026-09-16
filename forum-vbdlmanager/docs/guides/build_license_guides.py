#!/usr/bin/env python3
"""Build HDD Land Company English training PDFs for license desks."""
from __future__ import annotations

import os
from reportlab.lib.pagesizes import A4
from reportlab.lib.units import mm, inch
from reportlab.lib.colors import HexColor, white, black
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_JUSTIFY
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Image, Table, TableStyle,
    PageBreak, KeepTogether, ListFlowable, ListItem, HRFlowable,
)
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont

OUT_DIR = "/workspace/forum-vbdlmanager/docs/guides"
SHOT = "/opt/cursor/artifacts/guide-shots/final"
ART = "/opt/cursor/artifacts"

BRAND = HexColor("#0B3D5C")
ACCENT = HexColor("#148C7A")
INK = HexColor("#102A43")
MUTED = HexColor("#486581")
LINE = HexColor("#D9E2EC")
SOFT = HexColor("#F0F4F8")
WARN = HexColor("#9A6700")

os.makedirs(OUT_DIR, exist_ok=True)
os.makedirs(ART, exist_ok=True)


def styles():
    s = getSampleStyleSheet()
    s.add(ParagraphStyle(
        name="CoverTitle", fontName="Helvetica-Bold", fontSize=26,
        leading=32, textColor=white, alignment=TA_CENTER, spaceAfter=8,
    ))
    s.add(ParagraphStyle(
        name="CoverSub", fontName="Helvetica", fontSize=13,
        leading=18, textColor=HexColor("#B6D0E2"), alignment=TA_CENTER, spaceAfter=6,
    ))
    s.add(ParagraphStyle(
        name="H1", fontName="Helvetica-Bold", fontSize=16,
        leading=21, textColor=BRAND, spaceBefore=14, spaceAfter=8,
    ))
    s.add(ParagraphStyle(
        name="H2", fontName="Helvetica-Bold", fontSize=12.5,
        leading=16, textColor=BRAND, spaceBefore=10, spaceAfter=5,
    ))
    s.add(ParagraphStyle(
        name="Body", fontName="Helvetica", fontSize=10.2,
        leading=14.5, textColor=INK, alignment=TA_JUSTIFY, spaceAfter=6,
    ))
    s.add(ParagraphStyle(
        name="GuideBullet", fontName="Helvetica", fontSize=10.2,
        leading=14.2, textColor=INK, leftIndent=8, spaceAfter=2,
    ))
    s.add(ParagraphStyle(
        name="Caption", fontName="Helvetica-Oblique", fontSize=8.5,
        leading=11, textColor=MUTED, alignment=TA_CENTER, spaceBefore=3, spaceAfter=10,
    ))
    s.add(ParagraphStyle(
        name="StepTitle", fontName="Helvetica-Bold", fontSize=11,
        leading=14, textColor=ACCENT, spaceBefore=8, spaceAfter=3,
    ))
    s.add(ParagraphStyle(
        name="Note", fontName="Helvetica", fontSize=9.5,
        leading=13, textColor=WARN, backColor=HexColor("#FFF8E6"),
        borderPadding=6, spaceBefore=6, spaceAfter=8,
    ))
    s.add(ParagraphStyle(
        name="Footer", fontName="Helvetica", fontSize=8,
        leading=10, textColor=MUTED, alignment=TA_CENTER,
    ))
    s.add(ParagraphStyle(
        name="Small", fontName="Helvetica", fontSize=9,
        leading=12, textColor=MUTED, spaceAfter=4,
    ))
    return s


def header_footer(canvas, doc, subtitle):
    canvas.saveState()
    canvas.setFillColor(BRAND)
    canvas.rect(0, A4[1] - 14 * mm, A4[0], 14 * mm, fill=1, stroke=0)
    canvas.setFillColor(white)
    canvas.setFont("Helvetica-Bold", 9)
    canvas.drawString(18 * mm, A4[1] - 9 * mm, "HDD Land Company")
    canvas.setFont("Helvetica", 8)
    canvas.drawRightString(A4[0] - 18 * mm, A4[1] - 9 * mm, subtitle)
    canvas.setStrokeColor(LINE)
    canvas.setLineWidth(0.5)
    canvas.line(18 * mm, 14 * mm, A4[0] - 18 * mm, 14 * mm)
    canvas.setFillColor(MUTED)
    canvas.setFont("Helvetica", 8)
    canvas.drawString(18 * mm, 8 * mm, "forum.hdd-land.com")
    canvas.drawRightString(A4[0] - 18 * mm, 8 * mm, f"Page {doc.page}")
    canvas.restoreState()


def cover_block(title, subtitle, audience, st):
    data = [[
        Paragraph(f"<b>HDD Land Company</b>", st["CoverSub"]),
    ], [
        Paragraph(title, st["CoverTitle"]),
    ], [
        Paragraph(subtitle, st["CoverSub"]),
    ], [
        Paragraph(audience, st["CoverSub"]),
    ]]
    t = Table(data, colWidths=[170 * mm])
    t.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, -1), BRAND),
        ("TOPPADDING", (0, 0), (-1, 0), 22),
        ("TOPPADDING", (0, 1), (-1, 1), 8),
        ("BOTTOMPADDING", (0, -1), (-1, -1), 22),
        ("LEFTPADDING", (0, 0), (-1, -1), 16),
        ("RIGHTPADDING", (0, 0), (-1, -1), 16),
        ("ALIGN", (0, 0), (-1, -1), "CENTER"),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
    ]))
    return t


def fig(path, width=165 * mm, caption="", st=None):
    if not os.path.exists(path):
        return Paragraph(f"[Image missing: {os.path.basename(path)}]", st["Caption"] if st else styles()["Caption"])
    im = Image(path)
    iw, ih = im.imageWidth, im.imageHeight
    ratio = width / float(iw)
    im.drawWidth = width
    im.drawHeight = ih * ratio
    # Cap height so one figure doesn't blow a page
    max_h = 105 * mm
    if im.drawHeight > max_h:
        scale = max_h / im.drawHeight
        im.drawWidth *= scale
        im.drawHeight *= scale
    parts = [im]
    if caption and st:
        parts.append(Paragraph(caption, st["Caption"]))
    return KeepTogether(parts)


def bullets(items, st):
    return ListFlowable(
        [ListItem(Paragraph(i, st["GuideBullet"]), leftIndent=12, bulletColor=ACCENT) for i in items],
        bulletType="bullet",
        start="•",
        leftIndent=10,
        bulletFontSize=10,
    )


def build_license_request_pdf():
    st = styles()
    path = os.path.join(OUT_DIR, "HDD-Land-Company-License-Request-Customer-Guide.pdf")
    art = os.path.join(ART, "HDD-Land-Company-License-Request-Customer-Guide.pdf")
    doc = SimpleDocTemplate(
        path, pagesize=A4,
        leftMargin=16 * mm, rightMargin=16 * mm,
        topMargin=20 * mm, bottomMargin=18 * mm,
        title="HDD Land Company — License Request Customer Guide",
        author="HDD Land Company",
    )
    story = []
    story.append(cover_block(
        "License Request",
        "Customer training guide — send a payment receipt and receive your license",
        "Audience: Forum customers · English",
        st,
    ))
    story.append(Spacer(1, 10 * mm))
    story.append(Paragraph(
        "This guide explains how customers use the <b>License Request</b> menu on "
        "the HDD Land forum to submit a payment receipt, track the request in Message Center, "
        "and receive the returned license file.",
        st["Body"],
    ))
    story.append(Paragraph(
        "Official portal: <b>https://forum.hdd-land.com/</b> &nbsp;·&nbsp; "
        "Desk: <b>/vbdlmanager/sediv_license_request.php</b>",
        st["Small"],
    ))

    story.append(Paragraph("1. What this service does", st["H1"]))
    story.append(Paragraph(
        "License Request is for customers who have paid for a SeDiv license and need the "
        "license file delivered back into their forum account. You upload a clear payment "
        "receipt. HDD Land opens a private Message Center ticket, emails the receipt from "
        "<b>info@hdd-land.com</b> to the activator inbox <b>sedivlic@list.ru</b>, and later "
        "posts the returned <b>.txt</b> license into the same ticket.",
        st["Body"],
    ))
    story.append(bullets([
        "You must be signed in to the HDD Land forum.",
        "Accepted receipt files: <b>jpg, jpeg, png, webp, gif, pdf</b> (max 8 MB).",
        "Each request gets a unique tracking token: <b>VBDL-REQ-XXXXXXXXXXXX</b>.",
        "Email subject format: <b>License Request [VBDL-REQ-…]</b>.",
        "After approval, an admin can add you to <b>VIP SeDiv</b> so you can use Active License SeDiv.",
    ], st))

    story.append(Paragraph("2. Open the License Request desk", st["H1"]))
    story.append(Paragraph("Step 1 — Sign in", st["StepTitle"]))
    story.append(Paragraph(
        "Go to <b>https://forum.hdd-land.com/</b> and sign in with your customer account.",
        st["Body"],
    ))
    story.append(Paragraph("Step 2 — Open Message Center", st["StepTitle"]))
    story.append(Paragraph(
        "Open <b>Message Center</b> from the forum navigation. In the license menus, choose "
        "<b>License Request</b> (or open the desk URL directly).",
        st["Body"],
    ))
    story.append(fig(
        os.path.join(SHOT, "license-request-desk.png"),
        160 * mm,
        "Figure 1. HDD Land Company — License Request desk (customer view)",
        st,
    ))

    story.append(Paragraph("3. Send your payment receipt", st["H1"]))
    story.append(Paragraph(
        "On the desk you will see your username, email, destination address, and subject. "
        "These fields are locked for accuracy — do not try to change the recipient.",
        st["Body"],
    ))
    story.append(bullets([
        "<b>To:</b> sedivlic@list.ru",
        "<b>Subject:</b> License Request (system adds your token automatically)",
        "<b>From / Reply-To:</b> info@hdd-land.com (handled by the server)",
    ], st))
    story.append(Paragraph("Step 3 — Attach the receipt", st["StepTitle"]))
    story.append(Paragraph(
        "Click the file field and select a clear photo or PDF of your payment receipt. "
        "Make sure the payer name, amount, date, and reference are readable.",
        st["Body"],
    ))
    story.append(Paragraph("Step 4 — Optional note", st["StepTitle"]))
    story.append(Paragraph(
        "Add a short note if needed (order number, product name, or payment method). "
        "Keep it factual — this note is included in the email body.",
        st["Body"],
    ))
    story.append(Paragraph("Step 5 — Press Send request", st["StepTitle"]))
    story.append(Paragraph(
        "Click <b>Send request</b>. The system will:",
        st["Body"],
    ))
    story.append(bullets([
        "Create a Message Center ticket titled <b>License Request - VBDL-REQ-…</b>",
        "Attach your receipt to that ticket",
        "Email the receipt to sedivlic@list.ru with your unique token",
        "Show a success message and a link to open the ticket",
    ], st))

    story.append(Paragraph("4. Track status and receive the license", st["H1"]))
    story.append(fig(
        os.path.join(SHOT, "flow-license-request.png"),
        160 * mm,
        "Figure 2. End-to-end License Request process",
        st,
    ))
    story.append(Paragraph("Status meanings", st["H2"]))
    story.append(bullets([
        "<b>sent</b> — receipt emailed; waiting for activator reply",
        "<b>approved</b> — license <b>.txt</b> received and attached to your ticket",
        "<b>rejected</b> — activator replied with text only (instructions or problem); read the ticket",
        "<b>vip_added</b> — admin added you to VIP SeDiv; you may use Active License SeDiv",
    ], st))
    story.append(Paragraph("Step 6 — Open Message Center", st["StepTitle"]))
    story.append(Paragraph(
        "Open the ticket from the success link or from <b>Your requests</b> on the desk. "
        "When the license arrives, you will see an approval note and a downloadable "
        "<b>.txt</b> attachment in the same conversation.",
        st["Body"],
    ))
    story.append(fig(
        os.path.join(SHOT, "message-center-tickets.png"),
        155 * mm,
        "Figure 3. Example Message Center tickets (License Request + Active License)",
        st,
    ))

    story.append(Paragraph("5. After approval — VIP SeDiv", st["H1"]))
    story.append(Paragraph(
        "Approved means the license file is in your ticket. An HDD Land administrator still "
        "needs to add your account to the <b>VIP SeDiv</b> group. After that, your status "
        "becomes <b>vip_added</b> and you can use the <b>Active License SeDiv</b> menu to "
        "activate product licenses (separate guide).",
        st["Body"],
    ))

    story.append(Paragraph("6. Important rules for accurate matching", st["H1"]))
    story.append(Paragraph(
        "Many license emails may be sent on the same day. Matching relies on your unique token.",
        st["Body"],
    ))
    story.append(bullets([
        "Never remove or edit the <b>VBDL-REQ-…</b> token from ticket titles or forwarded mail.",
        "If you contact support about a request, always quote the full token.",
        "Do not upload the same receipt twice unless staff asks you to create a new request.",
        "If status is <b>rejected</b>, read the activator message in the ticket before sending again.",
    ], st))
    story.append(Paragraph(
        "<b>Need help?</b> Contact HDD Land support through Message Center or "
        "info@hdd-land.com and include your VBDL-REQ token.",
        st["Note"],
    ))

    story.append(Paragraph("7. Quick checklist", st["H1"]))
    story.append(bullets([
        "Signed in to forum.hdd-land.com",
        "Opened License Request desk",
        "Uploaded a clear receipt (jpg/png/pdf)",
        "Pressed Send request and saved the VBDL-REQ token",
        "Opened the Message Center ticket",
        "Downloaded license.txt when status = approved",
        "Waited for admin VIP add before using Active License SeDiv",
    ], st))
    story.append(Spacer(1, 8 * mm))
    story.append(HRFlowable(width="100%", thickness=0.6, color=LINE))
    story.append(Paragraph(
        "© HDD Land Company · Professional Data Recovery · Training document · English",
        st["Footer"],
    ))

    def _hf(c, d):
        header_footer(c, d, "License Request · Customer Guide")

    doc.build(story, onFirstPage=_hf, onLaterPages=_hf)
    # copy to artifacts
    import shutil
    shutil.copy2(path, art)
    print("Wrote", path)
    return path


def build_active_license_pdf():
    st = styles()
    path = os.path.join(OUT_DIR, "HDD-Land-Company-Active-License-SeDiv-Guide.pdf")
    art = os.path.join(ART, "HDD-Land-Company-Active-License-SeDiv-Guide.pdf")
    doc = SimpleDocTemplate(
        path, pagesize=A4,
        leftMargin=16 * mm, rightMargin=16 * mm,
        topMargin=20 * mm, bottomMargin=18 * mm,
        title="HDD Land Company — Active License SeDiv Guide",
        author="HDD Land Company",
    )
    story = []
    story.append(cover_block(
        "Active License SeDiv",
        "Complete guide — send a .lic file and receive the activated .src",
        "Audience: SeDiv VIP members · English",
        st,
    ))
    story.append(Spacer(1, 10 * mm))
    story.append(Paragraph(
        "This guide explains the full <b>Active License SeDiv</b> workflow on the HDD Land forum: "
        "choosing the correct product, uploading a <b>.lic</b> file, tracking the Message Center "
        "ticket, and downloading the activated <b>.src</b> return.",
        st["Body"],
    ))
    story.append(Paragraph(
        "Official portal: <b>https://forum.hdd-land.com/</b> &nbsp;·&nbsp; "
        "Desk: <b>/vbdlmanager/sediv_active_license.php</b>",
        st["Small"],
    ))

    story.append(Paragraph("1. Who can use this menu", st["H1"]))
    story.append(Paragraph(
        "Active License SeDiv is available only to members of the <b>VIP SeDiv</b> group. "
        "If you do not see the menu, complete a <b>License Request</b> first and wait until "
        "an administrator adds VIP SeDiv to your account.",
        st["Body"],
    ))
    story.append(bullets([
        "VIP users send licenses themselves from the desk.",
        "Support staff can review every ticket in Message Center.",
        "Mail is always sent from <b>info@hdd-land.com</b> to <b>sedivlic@list.ru</b>.",
    ], st))

    story.append(Paragraph("2. Open the Active License SeDiv desk", st["H1"]))
    story.append(Paragraph("Step 1 — Sign in as VIP", st["StepTitle"]))
    story.append(Paragraph(
        "Sign in at <b>https://forum.hdd-land.com/</b> with your VIP SeDiv account.",
        st["Body"],
    ))
    story.append(Paragraph("Step 2 — Open the desk", st["StepTitle"]))
    story.append(Paragraph(
        "From Message Center open <b>active license sediv</b>, or go directly to the desk URL. "
        "You should see the product list, upload field, and your ticket history.",
        st["Body"],
    ))
    story.append(fig(
        os.path.join(SHOT, "active-license-desk.png"),
        158 * mm,
        "Figure 1. HDD Land Company — Active License SeDiv desk",
        st,
    ))

    story.append(Paragraph("3. Choose the correct license type", st["H1"]))
    story.append(Paragraph(
        "Each product uses an exact email subject that the activator recognizes. "
        "Select the matching radio button before you upload the file.",
        st["Body"],
    ))
    # Product table
    rows = [[
        Paragraph("<b>License type</b>", st["Small"]),
        Paragraph("<b>Exact email subject</b>", st["Small"]),
    ]]
    products = [
        ("All license SeDiv imager", "Subject license SeDiv imager"),
        ("All license SeHitachi imager", "Subject license SeHitachi imager"),
        ("All license SeDivX imager", "Subject license SeDivX imager"),
        ("All license SeHGST imager", "Subject license SeHGST imager"),
        ("All license SeDiv repairs", "Subject license SeDiv repairs"),
        ("All license SeHitachi repairs", "Subject license SeHitachi repairs"),
    ]
    for label, subj in products:
        rows.append([
            Paragraph(label, st["Small"]),
            Paragraph(subj, st["Small"]),
        ])
    tbl = Table(rows, colWidths=[75 * mm, 90 * mm])
    tbl.setStyle(TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), BRAND),
        ("TEXTCOLOR", (0, 0), (-1, 0), white),
        ("BACKGROUND", (0, 1), (-1, -1), SOFT),
        ("GRID", (0, 0), (-1, -1), 0.4, LINE),
        ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
        ("LEFTPADDING", (0, 0), (-1, -1), 6),
        ("RIGHTPADDING", (0, 0), (-1, -1), 6),
        ("TOPPADDING", (0, 0), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [white, SOFT]),
    ]))
    # Fix header text color for Paragraphs in header - override by rebuilding header as plain
    story.append(tbl)
    story.append(Spacer(1, 3 * mm))
    story.append(Paragraph(
        "The desk preview updates the Subject line when you change the selected type. "
        "The system then appends your tracking token: "
        "<b>Subject license … [VBDL-LIC-…]</b>.",
        st["Body"],
    ))

    story.append(Paragraph("4. Send the .lic file", st["H1"]))
    story.append(Paragraph("Step 3 — Upload .lic", st["StepTitle"]))
    story.append(Paragraph(
        "Choose your license file. Only <b>.lic</b> files are accepted.",
        st["Body"],
    ))
    story.append(Paragraph("Step 4 — Optional note", st["StepTitle"]))
    story.append(Paragraph(
        "Add a short note if needed (device serial, urgency, or related ticket). "
        "The note is included in the outbound email body.",
        st["Body"],
    ))
    story.append(Paragraph("Step 5 — Press Send license", st["StepTitle"]))
    story.append(Paragraph("When you send, HDD Land automatically:", st["Body"]))
    story.append(bullets([
        "Opens a Message Center ticket shared with you and support",
        "Attaches your <b>.lic</b> to the ticket",
        "Creates tracking token <b>VBDL-LIC-XXXXXXXXXXXX</b>",
        "Emails sedivlic@list.ru from info@hdd-land.com with Message-ID + X-VBDL-Token headers",
        "Shows a success link to the new ticket",
    ], st))
    story.append(Paragraph(
        "<b>Tip:</b> You may send several license types the same day. Each send creates its "
        "own token and ticket — never mix files between tickets.",
        st["Note"],
    ))

    story.append(Paragraph("5. Receive the activated .src", st["H1"]))
    story.append(fig(
        os.path.join(SHOT, "flow-active-license.png"),
        160 * mm,
        "Figure 2. Active License SeDiv send → activate → return flow",
        st,
    ))
    story.append(Paragraph(
        "When the activator replies, the forum inbox poller matches the reply to your ticket "
        "using this priority:",
        st["Body"],
    ))
    story.append(bullets([
        "X-VBDL-Token header / In-Reply-To Message-ID",
        "Token in the email subject",
        "Token in the email body",
        "Exact product subject — only if exactly one open ticket matches",
    ], st))
    story.append(Paragraph("Step 6 — Refresh your ticket list", st["StepTitle"]))
    story.append(Paragraph(
        "On the desk, open <b>Your license tickets</b> and press Refresh. Status values:",
        st["Body"],
    ))
    story.append(bullets([
        "<b>sent</b> — emailed; waiting for activator",
        "<b>returned</b> — activated <b>.src</b> attached to the Message Center ticket",
        "<b>rejected</b> — text reply only (activation failed / instructions). Read the ticket text.",
        "<b>error</b> — rare system failure; contact support with the token",
    ], st))
    story.append(Paragraph("Step 7 — Download Source.src", st["StepTitle"]))
    story.append(Paragraph(
        "Open the Message Center ticket. Download the <b>.src</b> attachment and use it in "
        "SeDiv for the matching product. Keep the token if you need support later.",
        st["Body"],
    ))
    story.append(fig(
        os.path.join(SHOT, "message-center-tickets.png"),
        155 * mm,
        "Figure 3. Example ticket with .lic sent and .src returned",
        st,
    ))

    story.append(Paragraph("6. If activation fails (rejected)", st["H1"]))
    story.append(Paragraph(
        "If the activator cannot activate the file, a plain-text reply is posted into your "
        "ticket and status becomes <b>rejected</b>. Automatic acknowledgment messages are ignored "
        "and will not mark your ticket rejected. Read the activator message carefully, fix the "
        "license/source issue, then send a <b>new</b> request with a new .lic.",
        st["Body"],
    ))

    story.append(Paragraph("7. Retention and downloads", st["H1"]))
    story.append(bullets([
        "Returned <b>.src</b> files remain available for download for a limited retention window (default 7 days).",
        "After retention, the binary may be purged while the ticket history and filename remain visible.",
        "Download your .src promptly after status becomes <b>returned</b>.",
    ], st))

    story.append(Paragraph("8. Accuracy rules for multi-license days", st["H1"]))
    story.append(Paragraph(
        "On busy days many licenses share the same product subject. Always rely on the token.",
        st["Body"],
    ))
    story.append(bullets([
        "One .lic file → one Send → one VBDL-LIC token → one ticket",
        "Do not rename or strip the token from subjects when forwarding",
        "If you manually return a file for staff, paste the exact token with the .src",
        "Quote the full token in any support conversation",
    ], st))

    story.append(Paragraph("9. Quick checklist", st["H1"]))
    story.append(bullets([
        "VIP SeDiv membership confirmed",
        "Opened Active License SeDiv desk",
        "Selected the correct product type / subject",
        "Uploaded the matching .lic file",
        "Pressed Send license and saved VBDL-LIC token",
        "Opened Message Center ticket",
        "Waited until status = returned",
        "Downloaded Source.src and used it in SeDiv",
    ], st))
    story.append(Spacer(1, 6 * mm))
    story.append(Paragraph(
        "<b>Need help?</b> Open the ticket in Message Center or email info@hdd-land.com "
        "with your VBDL-LIC token.",
        st["Note"],
    ))
    story.append(HRFlowable(width="100%", thickness=0.6, color=LINE))
    story.append(Paragraph(
        "© HDD Land Company · Professional Data Recovery · Training document · English",
        st["Footer"],
    ))

    def _hf(c, d):
        header_footer(c, d, "Active License SeDiv · Complete Guide")

    doc.build(story, onFirstPage=_hf, onLaterPages=_hf)
    import shutil
    shutil.copy2(path, art)
    print("Wrote", path)
    return path


if __name__ == "__main__":
    p1 = build_license_request_pdf()
    p2 = build_active_license_pdf()
    for p in (p1, p2):
        print(p, os.path.getsize(p), "bytes")
