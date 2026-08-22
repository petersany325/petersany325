@echo off
chcp 65001 >nul
:: WxWdPass v3 — Windows 11 / 10 x64 AHCI install (Run as Administrator)
cd /d "%~dp0"

net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
  echo.
  echo  ERROR: Run as Administrator ^(right-click → Run as administrator^)
  echo.
  pause
  exit /b 1
)

echo.
echo  Windex WD — WxWdPass v3  ^(Windows 11 / 10 x64 AHCI^)
echo  ====================================================
echo  NEVER lock the OS / boot disk.
echo  BIOS must be AHCI ^(not legacy IDE^).
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0INSTALL-WIN11-AHCI.ps1" -DataRoot "D:\test windex"
set ERR=%ERRORLEVEL%
echo.
if %ERR% NEQ 0 (
  echo Install failed. See driver\WxWdPass-v3\docs\
) else (
  echo Run CHECK-DRIVER.bat to verify.
)
pause
exit /b %ERR%
