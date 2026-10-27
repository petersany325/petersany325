#!/usr/bin/env python3
"""Build JMS578 SATA-USB 2-layer stick PCB (KiCad 7) and export Gerbers for fab."""

from __future__ import annotations

import shutil
import subprocess
import sys
import zipfile
from pathlib import Path

MM = 1_000_000
FP_ROOT = Path("/usr/share/kicad/footprints")
OUT_DIR = Path(__file__).resolve().parent
ROOT = OUT_DIR.parent
GERBER_DIR = ROOT / "gerbers"
ARTIFACT_DIR = Path("/opt/cursor/artifacts")


def mm(x: float) -> int:
    return int(round(x * MM))


def main() -> None:
    import wx

    app = wx.App(False)
    sys.path.insert(0, "/usr/lib/python3/dist-packages")
    import pcbnew

    print("pcbnew ready", pcbnew.GetBuildVersion(), flush=True)

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    ARTIFACT_DIR.mkdir(parents=True, exist_ok=True)

    board = pcbnew.BOARD()
    drc = board.GetDesignSettings()
    drc.SetBoardThickness(mm(1.6))
    drc.m_TrackMinWidth = mm(0.15)
    drc.m_ViasMinSize = mm(0.6)
    drc.m_ViasMinDrill = mm(0.3)
    print("board created", flush=True)

    def ensure_net(name: str) -> int:
        existing = board.FindNet(name)
        if existing:
            return existing.GetNetCode()
        net = pcbnew.NETINFO_ITEM(board, name)
        board.Add(net)
        return net.GetNetCode()

    gnd = ensure_net("GND")
    v5_usb = ensure_net("V5_USB")
    v5_dc = ensure_net("V5_DC")
    v5_or = ensure_net("V5_OR")
    v5_sw = ensure_net("V5_SW")
    v3 = ensure_net("3V3")
    print("nets ok", flush=True)

    # Edge cuts 85x22
    for (x1, y1), (x2, y2) in [
        ((0, 0), (85, 0)),
        ((85, 0), (85, 22)),
        ((85, 22), (0, 22)),
        ((0, 22), (0, 0)),
    ]:
        seg = pcbnew.PCB_SHAPE(board)
        seg.SetShape(pcbnew.SHAPE_T_SEGMENT)
        seg.SetStart(pcbnew.VECTOR2I(mm(x1), mm(y1)))
        seg.SetEnd(pcbnew.VECTOR2I(mm(x2), mm(y2)))
        seg.SetLayer(pcbnew.Edge_Cuts)
        seg.SetWidth(mm(0.15))
        board.Add(seg)
    print("edge ok", flush=True)

    def add_fp(pretty, name, ref, value, x, y):
        fp = pcbnew.FootprintLoad(str(FP_ROOT / pretty), name)
        if fp is None:
            raise RuntimeError(f"missing footprint {pretty}:{name}")
        fp.SetReference(ref)
        fp.SetValue(value)
        fp.SetPosition(pcbnew.VECTOR2I(mm(x), mm(y)))
        board.Add(fp)
        print("fp", ref, flush=True)
        return fp

    j1 = add_fp("Connector_USB.pretty", "USB_A_Molex_48037-2200_Horizontal", "J1", "USB3-A", 8, 11)
    sw1 = add_fp("Button_Switch_THT.pretty", "SW_Slide_SPDT_Straight_CK_OS102011MS2Q", "SW1", "ON/OFF", 22, 11)
    j2 = add_fp("Connector_BarrelJack.pretty", "BarrelJack_Horizontal", "J2", "DC5V", 32, 11)
    u1 = add_fp("Package_DFN_QFN.pretty", "QFN-48-1EP_6x6mm_P0.4mm_EP4.2x4.2mm_ThermalVias", "U1", "JMS578", 48, 8)
    u2 = add_fp("Package_SO.pretty", "SOIC-8_3.9x4.9mm_P1.27mm", "U2", "Flash512K", 58, 6)
    y1 = add_fp("Crystal.pretty", "Crystal_SMD_3225-4Pin_3.2x2.5mm", "Y1", "30MHz", 58, 14)
    j3 = add_fp("Connector_SATA_SAS.pretty", "SATA_Amphenol_10029364-001LF_Horizontal", "J3", "SATA", 74, 11)
    add_fp("Inductor_SMD.pretty", "L_0805_2012Metric", "L1", "4.7uH", 42, 16.5)
    add_fp("Resistor_SMD.pretty", "R_0402_1005Metric", "R1", "12k", 42, 4)
    add_fp("LED_SMD.pretty", "LED_0805_2012Metric", "D3", "ACT", 64, 17.5)

    for pad in j1.Pads():
        n = pad.GetNumber()
        if n == "1":
            pad.SetNetCode(v5_usb)
        if n in ("4", "5", "6", "0"):
            pad.SetNetCode(gnd)
    for pad in sw1.Pads():
        n = pad.GetNumber()
        if n == "1":
            pad.SetNetCode(v5_or)
        elif n == "2":
            pad.SetNetCode(v5_sw)
        elif n == "3":
            pad.SetNetCode(v5_or)
    for pad in j2.Pads():
        pad.SetNetCode(v5_dc if pad.GetNumber() == "1" else gnd)
    for pad in u1.Pads():
        if pad.GetNumber() in ("49", "EP", "0"):
            pad.SetNetCode(gnd)
    for pad in u2.Pads():
        if pad.GetNumber() == "4":
            pad.SetNetCode(gnd)
        if pad.GetNumber() == "8":
            pad.SetNetCode(v3)
    for pad in j3.Pads():
        if pad.GetNumber() in ("2", "3", "5", "6"):
            pad.SetNetCode(gnd)
    print("nets assigned", flush=True)

    def track(x1, y1, x2, y2, w, layer, netcode):
        tr = pcbnew.PCB_TRACK(board)
        tr.SetStart(pcbnew.VECTOR2I(mm(x1), mm(y1)))
        tr.SetEnd(pcbnew.VECTOR2I(mm(x2), mm(y2)))
        tr.SetWidth(mm(w))
        tr.SetLayer(layer)
        tr.SetNetCode(netcode)
        board.Add(tr)

    track(12, 4, 22, 4, 0.6, pcbnew.F_Cu, v5_usb)
    track(22, 4, 32, 4, 0.6, pcbnew.F_Cu, v5_or)
    track(22, 18, 48, 18, 0.8, pcbnew.F_Cu, v5_sw)
    track(48, 18, 74, 18, 0.8, pcbnew.F_Cu, v5_sw)

    for text, x, y, sz in [
        ("JMS578 SATA-USB", 40, 2.2, 1.2),
        ("ON", 20.5, 3.5, 1.0),
        ("OFF", 20.2, 19.5, 1.0),
        ("2L 85x22", 40, 20.5, 0.9),
        ("DC5V", 30, 2.5, 0.9),
    ]:
        t = pcbnew.PCB_TEXT(board)
        t.SetText(text)
        t.SetPosition(pcbnew.VECTOR2I(mm(x), mm(y)))
        t.SetTextSize(pcbnew.VECTOR2I(mm(sz), mm(sz)))
        t.SetTextThickness(mm(sz * 0.15))
        t.SetLayer(pcbnew.F_SilkS)
        board.Add(t)
    print("silk ok", flush=True)

    def add_zone(layer):
        zone = pcbnew.ZONE(board)
        zone.SetNetCode(gnd)
        zone.SetLayer(layer)
        zone.SetPadConnection(pcbnew.ZONE_CONNECTION_THERMAL)
        zone.SetMinThickness(mm(0.2))
        zone.SetThermalReliefGap(mm(0.3))
        zone.SetThermalReliefSpokeWidth(mm(0.3))
        outline = zone.Outline()
        outline.NewOutline()
        for x, y in [(0.3, 0.3), (84.7, 0.3), (84.7, 21.7), (0.3, 21.7)]:
            outline.Append(mm(x), mm(y))
        board.Add(zone)

    add_zone(pcbnew.B_Cu)
    add_zone(pcbnew.F_Cu)
    print("zones added", flush=True)

    pcb_path = OUT_DIR / "jms578-sata-usb.kicad_pcb"
    pcbnew.SaveBoard(str(pcb_path), board)
    print("saved unfilled", pcb_path, flush=True)

    # Fill after reload — ZONE_FILLER segfaults on freshly built boards in this build
    board = pcbnew.LoadBoard(str(pcb_path))
    filler = pcbnew.ZONE_FILLER(board)
    ok = filler.Fill(board.Zones())
    print("fill", ok, flush=True)
    pcbnew.SaveBoard(str(pcb_path), board)
    print("saved filled", pcb_path, flush=True)

    (OUT_DIR / "jms578-sata-usb.kicad_pro").write_text(
        '{\n  "meta": {"filename": "jms578-sata-usb.kicad_pro", "version": 1},\n'
        '  "sheets": [["root", ""]],\n  "text_variables": {}\n}\n',
        encoding="utf-8",
    )

    if GERBER_DIR.exists():
        shutil.rmtree(GERBER_DIR)
    GERBER_DIR.mkdir(parents=True)

    subprocess.check_call(
        [
            "kicad-cli",
            "pcb",
            "export",
            "gerbers",
            "--output",
            str(GERBER_DIR),
            "--layers",
            "F.Cu,B.Cu,F.SilkS,B.SilkS,F.Mask,B.Mask,Edge.Cuts",
            "--subtract-soldermask",
            str(pcb_path),
        ]
    )
    subprocess.check_call(
        [
            "kicad-cli",
            "pcb",
            "export",
            "drill",
            "--output",
            str(GERBER_DIR) + "/",
            "--format",
            "excellon",
            "--excellon-units",
            "mm",
            str(pcb_path),
        ]
    )
    svg_path = ROOT / "pcb-preview.svg"
    subprocess.check_call(
        [
            "kicad-cli",
            "pcb",
            "export",
            "svg",
            "--output",
            str(svg_path),
            "--layers",
            "F.Cu,B.Cu,F.SilkS,Edge.Cuts",
            "--page-size-mode",
            "2",
            str(pcb_path),
        ]
    )

    zip_path = ROOT / "jms578-sata-usb-gerbers.zip"
    with zipfile.ZipFile(zip_path, "w", zipfile.ZIP_DEFLATED) as zf:
        for f in sorted(GERBER_DIR.iterdir()):
            zf.write(f, arcname=f.name)

    shutil.copy2(zip_path, ARTIFACT_DIR / zip_path.name)
    shutil.copy2(pcb_path, ARTIFACT_DIR / pcb_path.name)
    shutil.copy2(svg_path, ARTIFACT_DIR / svg_path.name)
    print("Gerber ZIP:", zip_path, flush=True)
    print("Files:", sorted(p.name for p in GERBER_DIR.iterdir()), flush=True)
    _ = app


if __name__ == "__main__":
    main()
