@echo off
chcp 65001 >nul
cd /d "%~dp0"
if exist "%~dp0WindexWD.exe" (
  start "" "%~dp0WindexWD.exe"
  exit /b 0
)
echo WindexWD.exe not found — run BUILD-EXE.bat once to create it.
call "%~dp0BUILD-EXE.bat"
