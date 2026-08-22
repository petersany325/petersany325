@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  WxWdPass v3 — CHECK ^(Windows 11^)
echo  ================================
echo.

net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 echo [--] Not elevated — run as Admin for full check

if exist "D:\test windex\driver\wxwd-state.json" (
  echo [OK] D:\test windex\driver\wxwd-state.json
  type "D:\test windex\driver\wxwd-state.json"
) else (
  echo [--] State file missing — run INSTALL-WIN11.bat
)

sc query WxWdPassAhci >nul 2>&1
if %ERRORLEVEL%==0 (echo [OK] WxWdPassAhci kernel service) else (echo [--] Kernel driver Phase 2 not loaded)

if exist "%~dp0..\service\WxWdIoService.ps1" (
  echo [OK] WxWdIoService.ps1 Phase 1 helper
) else (
  echo [FAIL] WxWdIoService.ps1 missing
)

if exist "%~dp0..\bin\WxWdPassAhci.sys" (
  echo [OK] WxWdPassAhci.sys present
) else (
  echo [--] WxWdPassAhci.sys not built ^(Phase 1 only^)
)

echo.
echo Scan disks:
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0..\service\WxWdIoService.ps1" -Action Scan -DataRoot "D:\test windex"
echo.
pause
