@echo off
chcp 65001 >nul
echo Legacy WdHd = Win7 32-bit only. Never OS disk.
pause
start "" "%~dp0driver\WdHdSetup\WdHdSetup.exe"
