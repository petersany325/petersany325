@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "DRV=%~dp0..\driver\WxWdPass-v3\install\INSTALL-WIN11.bat"
if not exist "%DRV%" set "DRV=%~dp0driver\WxWdPass-v3\install\INSTALL-WIN11.bat"
if not exist "%DRV%" (
  echo Run from D:\test windex\ — driver folder missing.
  pause
  exit /b 1
)
call "%DRV%"
