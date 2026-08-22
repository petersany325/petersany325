@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  Windex WD — UPDATE (local only, no web)
echo  =======================================
call "%~dp0UPDATE-LOCAL.bat"
call "%~dp0APPLY-PATH.bat"
