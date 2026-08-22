@echo off
chcp 65001 >nul
cd /d "%~dp0"
net session >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
  echo Run as Administrator.
  pause
  exit /b 1
)
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0INSTALL-WIN11-AHCI.ps1" -DataRoot "D:\test windex" -Uninstall
pause
