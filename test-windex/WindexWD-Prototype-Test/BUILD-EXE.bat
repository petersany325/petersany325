@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Building WindexWD.exe ...
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0BUILD-EXE.ps1"
pause
