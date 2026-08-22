@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  WxWdPass v3 — uninstall AHCI filter
echo  ===================================
echo.

if exist "%~dp0..\bin\WxWdPassSetup.exe" (
  echo Use WxWdPassSetup.exe -Uninstall when available.
) else (
  echo Manual: Device Manager -^> SATA AHCI Controller -^> uninstall WxWdPass filter
  echo Or: pnputil /delete-driver wxwdahci.inf /uninstall
)
echo.
pause
