@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  Windex WD — Driver install
echo  ==========================
echo.
echo  [1] Modern AHCI/SATA  Win10/11 x64  ^(WxWdPass v3^)
echo  [2] Legacy IDE        Win7 32-bit   ^(WdHd — lab only^)
echo.
choice /c 12 /n /m "Select [1] or [2]: "
if errorlevel 2 goto legacy
if errorlevel 1 goto ahci

:ahci
if exist "%~dp0..\driver\WxWdPass-v3\install\INSTALL-AHCI.bat" (
  call "%~dp0..\driver\WxWdPass-v3\install\INSTALL-AHCI.bat"
) else if exist "%~dp0driver\WxWdPass-v3\install\INSTALL-AHCI.bat" (
  call "%~dp0driver\WxWdPass-v3\install\INSTALL-AHCI.bat"
) else (
  echo WxWdPass v3 not found. See driver\WxWdPass-v3\docs\
  pause
)
exit /b 0

:legacy
echo.
echo WARNING: WdHd = Win XP/Vista/7 32-bit ONLY. Never on OS disk.
pause
if exist "%~dp0driver\WdHdSetup\WdHdSetup.exe" (
  start "" "%~dp0driver\WdHdSetup\WdHdSetup.exe"
) else (
  echo WdHdSetup not found under driver\WdHdSetup\
  pause
)
exit /b 0
