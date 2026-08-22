@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  WxWdPass v3 — AHCI driver check (HamGap / Windex WD)
echo  ======================================================
echo.

set OK=0
set FAIL=0

sc query WxWdPassAhci >nul 2>&1
if %ERRORLEVEL%==0 (
  echo [OK] Service WxWdPassAhci registered
  set /a OK+=1
) else (
  echo [--] Service WxWdPassAhci not installed yet
  echo      Phase 1: use WxWdIoService fallback ^(no kernel driver^)
  set /a FAIL+=1
)

if exist "%SystemRoot%\System32\drivers\WxWdPassAhci.sys" (
  echo [OK] WxWdPassAhci.sys present
  set /a OK+=1
) else (
  echo [--] WxWdPassAhci.sys not found ^(expected until phase 2 build^)
)

where WxWdIoService.exe >nul 2>&1
if %ERRORLEVEL%==0 (
  echo [OK] WxWdIoService.exe in PATH
  set /a OK+=1
) else if exist "%~dp0..\bin\WxWdIoService.exe" (
  echo [OK] WxWdIoService.exe in driver\WxWdPass-v3\bin
  set /a OK+=1
) else (
  echo [--] WxWdIoService.exe not built yet
)

echo.
echo Legacy WdHd ^(32-bit IDE only^):
if exist "%~dp0..\..\test-windex\driver\WdHdSetup\WdHdSetup.exe" (
  echo [REF] test-windex\driver\WdHdSetup\ — Win7 32-bit lab only
) else if exist "%~dp0..\WdHdSetup\WdHdSetup.exe" (
  echo [REF] WdHdSetup\ — Win7 32-bit lab only
)

echo.
echo Docs: driver\WxWdPass-v3\docs\WXWD-PASS-v3-SPEC.md
echo.
pause
