# -*- coding: utf-8 -*-
"""
HDD Land-TestFTD232 — FT232 authenticity (genuine vs counterfeit) heuristics.

Drop-in module. Does NOT rewrite EEPROM.
Verdict labels (Persian + English codes):
  GENUINE_LIKELY   -> آی‌سی احتمالاً اصلی
  COUNTERFEIT_SUSPECT -> آی‌سی مشکوک به تقلبی
  INCONCLUSIVE     -> نامشخص (نیاز به تست بیشتر / سیم‌کشی)

Integrate with Land-TestFTD232:
  from authenticity_check import run_authenticity_check, format_authenticity_report
  result = run_authenticity_check(ft_handle, device_info, eeprom_words, extras=...)
"""

from __future__ import print_function

import re
import time

# FT232R device type used by D2XX
FT_DEVICE_232R = 5
FTDI_VID = 0x0403
FTDI_PID_FT232R = 0x6001

# Known-good FT232R EEPROM signature patterns (heuristic, not cryptographic)
# word0 often 0x4000 for FT232R; word1 = VID 0x0403
EXPECTED_WORD0_MASK = 0xFF00
EXPECTED_WORD0_VAL = 0x4000


class AuthVerdict(object):
    GENUINE_LIKELY = "GENUINE_LIKELY"
    COUNTERFEIT_SUSPECT = "COUNTERFEIT_SUSPECT"
    INCONCLUSIVE = "INCONCLUSIVE"

    FA_LABELS = {
        GENUINE_LIKELY: u"آی‌سی احتمالاً اصلی",
        COUNTERFEIT_SUSPECT: u"آی‌سی مشکوک به تقلبی",
        INCONCLUSIVE: u"نامشخص — نیاز به تست بیشتر",
    }


def _score_serial(serial):
    """Genuine FTDI serials are typically 8 alphanumeric chars (e.g. ALxxxxxx)."""
    findings = []
    score = 0
    if not serial:
        findings.append(("FAIL", "serial empty"))
        return -2, findings
    s = str(serial).strip()
    if re.match(r"^[A-Za-z0-9]{7,10}$", s):
        score += 1
        findings.append(("PASS", "serial format OK (%s)" % s))
    else:
        score -= 1
        findings.append(("FAIL", "serial format unusual (%r)" % s))
    # Many clones use all-zero / placeholder serials
    if s in ("00000000", "FT000001", "A0000000", "XXXXXXXX"):
        score -= 3
        findings.append(("FAIL", "placeholder/clone serial pattern"))
    return score, findings


def _score_id_vid_pid(usb_id, device_type):
    findings = []
    score = 0
    try:
        uid = int(usb_id) if not isinstance(usb_id, int) else usb_id
    except Exception:
        findings.append(("FAIL", "cannot parse USB ID=%r" % (usb_id,)))
        return -2, findings

    vid = (uid >> 16) & 0xFFFF
    pid = uid & 0xFFFF
    if vid == FTDI_VID and pid == FTDI_PID_FT232R:
        score += 1
        findings.append(("PASS", "VID:PID = %04X:%04X (FT232R)" % (vid, pid)))
    else:
        score -= 2
        findings.append(("FAIL", "unexpected VID:PID = %04X:%04X" % (vid, pid)))

    if device_type == FT_DEVICE_232R:
        score += 1
        findings.append(("PASS", "D2XX device type=5 (FT232R)"))
    else:
        score -= 1
        findings.append(("FAIL", "device type=%r (expected 5=FT232R)" % (device_type,)))
    return score, findings


def _score_eeprom(words):
    """words: list/tuple of up to 64 uint16 EEPROM words (non-destructive read)."""
    findings = []
    score = 0
    if not words or len(words) < 2:
        findings.append(("FAIL", "EEPROM words missing"))
        return -2, findings

    w0 = int(words[0]) & 0xFFFF
    w1 = int(words[1]) & 0xFFFF
    if (w0 & EXPECTED_WORD0_MASK) == EXPECTED_WORD0_VAL:
        score += 1
        findings.append(("PASS", "EEPROM word0=0x%04X (FT232R-like)" % w0))
    else:
        score -= 1
        findings.append(("FAIL", "EEPROM word0=0x%04X unusual for FT232R" % w0))

    if w1 == FTDI_VID:
        score += 1
        findings.append(("PASS", "EEPROM word1=0x%04X (VID)" % w1))
    else:
        score -= 2
        findings.append(("FAIL", "EEPROM word1=0x%04X (expected 0x0403)" % w1))

    # All-zero / all-FF EEPROM is a strong clone/blank signal
    uniq = set(int(x) & 0xFFFF for x in words[:16])
    if uniq <= set([0x0000]) or uniq <= set([0xFFFF]):
        score -= 3
        findings.append(("FAIL", "EEPROM first-16 words blank/uniform — clone/blank suspect"))
    else:
        findings.append(("PASS", "EEPROM first-16 words not blank"))

    return score, findings


def _score_baud_behavior(extras):
    """
    extras may include:
      loopback_ok_38400, loopback_ok_460800, loopback_ok_921600 (bool)
      eslip_ok_460800 (bool)
      boot_banner_ok (bool)
    Non-standard baud accuracy is a common clone fail point.
    """
    findings = []
    score = 0
    if not extras:
        findings.append(("SKIP", "no baud/loopback extras — inconclusive weight"))
        return 0, findings

    lb384 = extras.get("loopback_ok_38400")
    lb460 = extras.get("loopback_ok_460800")
    lb921 = extras.get("loopback_ok_921600")
    eslip = extras.get("eslip_ok_460800")
    boot = extras.get("boot_banner_ok")

    if lb384 is True:
        score += 1
        findings.append(("PASS", "TX↔RX loopback @38400 OK"))
    elif lb384 is False:
        score -= 2
        findings.append(("FAIL", "TX↔RX loopback @38400 FAILED (chip/UART path bad)"))

    if lb460 is True:
        score += 2
        findings.append(("PASS", "TX↔RX loopback @460800 OK (good authenticity signal)"))
    elif lb460 is False:
        score -= 2
        findings.append(("FAIL", "TX↔RX loopback @460800 FAILED (common on clones)"))

    if lb921 is True:
        score += 1
        findings.append(("PASS", "TX↔RX loopback @921600 OK"))
    elif lb921 is False:
        # Many genuine chips also struggle at 921600 on bad wiring — soft weight
        score -= 1
        findings.append(("FAIL", "TX↔RX loopback @921600 FAILED"))

    if eslip is True:
        score += 1
        findings.append(("PASS", "WinFOF eSLIP @460800 OK"))
    elif eslip is False:
        # Can be wiring/drive — do not heavily punish alone
        findings.append(("SKIP", "eSLIP @460800 FAIL (may be board/drive, not IC)"))

    if boot is True:
        score += 1
        findings.append(("PASS", "Boot/SeaSerial banner @38400 seen"))
    elif boot is False:
        findings.append(("SKIP", "no boot banner (drive/wiring — not IC proof)"))

    return score, findings


def decide_verdict(total_score, hard_fail=False):
    if hard_fail:
        return AuthVerdict.COUNTERFEIT_SUSPECT
    if total_score >= 4:
        return AuthVerdict.GENUINE_LIKELY
    if total_score <= -2:
        return AuthVerdict.COUNTERFEIT_SUSPECT
    return AuthVerdict.INCONCLUSIVE


def run_authenticity_check(device_info, eeprom_words, extras=None):
    """
    device_info dict keys:
      description, serial, id (int 0x04036001), type (int, 5=FT232R)
    eeprom_words: sequence of int (64 words preferred)
    extras: optional loopback / eslip / boot flags

    Returns dict with verdict, fa_label, score, steps (list of (status, msg))
    """
    extras = extras or {}
    steps = []
    score = 0

    desc = (device_info or {}).get("description") or ""
    serial = (device_info or {}).get("serial") or ""
    usb_id = (device_info or {}).get("id")
    dtype = (device_info or {}).get("type")

    try:
        id_hex = "0x%08X" % (int(usb_id) & 0xFFFFFFFF,)
    except Exception:
        id_hex = repr(usb_id)
    steps.append(("INFO", "desc=%s SN=%s ID=%s type=%s" % (desc, serial, id_hex, dtype)))

    s, f = _score_id_vid_pid(usb_id, dtype)
    score += s
    steps.extend(f)

    s, f = _score_serial(serial)
    score += s
    steps.extend(f)

    s, f = _score_eeprom(eeprom_words)
    score += s
    steps.extend(f)

    s, f = _score_baud_behavior(extras)
    score += s
    steps.extend(f)

    # Hard fail: claimed FT232R but VID wrong or blank EEPROM
    hard = False
    for st, msg in steps:
        if st == "FAIL" and ("unexpected VID:PID" in msg or "blank/uniform" in msg or "placeholder/clone" in msg):
            hard = True

    verdict = decide_verdict(score, hard_fail=hard)
    return {
        "verdict": verdict,
        "fa_label": AuthVerdict.FA_LABELS[verdict],
        "score": score,
        "steps": steps,
        "disclaimer": (
            u"این یک تشخیص قطعی آزمایشگاهی نیست؛ بر اساس امضای D2XX/EEPROM "
            u"و رفتار baud است. برای اطمینان، با نمونه اصل شناخته‌شده مقایسه کنید."
        ),
    }


def format_authenticity_report(result):
    """Plain-text block matching Land-Test report style."""
    lines = []
    lines.append("=== AUTHENTICITY / اصل یا تقلبی ===")
    lines.append("VERDICT | %s | %s" % (result["verdict"], result["fa_label"]))
    lines.append("SCORE | %s" % result["score"])
    for st, msg in result["steps"]:
        lines.append("%s | Authenticity | %s" % (st, msg))
    lines.append("NOTE | %s" % result["disclaimer"])
    return "\n".join(lines)


def demo_from_report_sample():
    """Self-check using values from user's v7.4 report (AL03F8QZ)."""
    info = {
        "description": "FT232R USB UART",
        "serial": "AL03F8QZ",
        "id": 0x04036001,
        "type": 5,
    }
    # Only first two words known from report; pad neutrally
    words = [0x4000, 0x0403] + [0x1234] * 14 + [0] * 48
    extras = {
        "boot_banner_ok": False,
        "eslip_ok_460800": False,
        # loopback not run in that report
    }
    return run_authenticity_check(info, words, extras)


if __name__ == "__main__":
    import sys
    r = demo_from_report_sample()
    text = format_authenticity_report(r)
    if sys.version_info[0] < 3:
        print(text.encode("utf-8"))
    else:
        print(text)
