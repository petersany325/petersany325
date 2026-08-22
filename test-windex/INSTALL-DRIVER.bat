@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  Windex WD — Driver install
echo  ==========================
echo.
echo  [1] Windows 11 / 10 x64  — WxWdPass v3 AHCI ^(recommended^)
echo  [2] Legacy WdHd          — Win7 32-bit IDE lab ONLY
echo.
choice /c 12 /n /m "Select [1] or [2]: "
if errorlevel 2 goto legacy
if errorlevel 1 goto win11

:win11
set "DRV=%~dp0driver\WxWdPass-v3\install\INSTALL-WIN11.bat"
if not exist "%DRV%" set "DRV=%~dp0..\driver\WxWdPass-v3\install\INSTALL-WIN11.bat"
if not exist "%DRV%" (
  echo ERROR: WxWdPass v3 not found under driver\WxWdPass-v3\
  pause
  exit /b 1
)
call "%DRV%"
exit /b %ERRORLEVEL%

:legacy
echo.
echo NOT for Windows 11. Win XP/Vista/7 32-bit only. Never on OS disk.
pause
if exist "%~dp0driver\WdHdSetup\WdHdSetup.exe" (
  start "" "%~dp0driver\WdHdSetup\WdHdSetup.exe"
) else (
  echo WdHdSetup not found.
  pause
)
exit /b 0
