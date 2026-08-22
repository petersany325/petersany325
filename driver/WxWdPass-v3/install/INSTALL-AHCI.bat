@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  WxWdPass v3 — AHCI PassThrough install
echo  ======================================
echo.
echo  Target: Windows 10/11 x64, BIOS AHCI mode
echo  NEVER install on the OS/boot disk port.
echo.
echo  Status: Phase 2 kernel driver not built yet.
echo  Phase 1 fallback: WxWdIoService ^(ATA pass-through, no port replace^)
echo.
echo  See: ..\docs\WXWD-PASS-v3-SPEC.md
echo.

if not exist "%~dp0..\bin\WxWdPassSetup.exe" (
  echo [INFO] WxWdPassSetup.exe not found — run CHECK-DRIVER.bat
  echo.
  if exist "%~dp0CHECK-DRIVER.bat" call "%~dp0CHECK-DRIVER.bat"
  exit /b 1
)

echo Starting WxWdPassSetup.exe ...
start "" "%~dp0..\bin\WxWdPassSetup.exe"
exit /b 0
