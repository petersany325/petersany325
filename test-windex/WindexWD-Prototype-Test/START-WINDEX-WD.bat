@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "HTML=%~dp0resources\app\index.html"
set "EXE=%~dp0WindexWD.exe"
set "PORT=8765"
set "URL=http://127.0.0.1:%PORT%/"

echo.
echo  Windex WD — HamGap
echo  ==================

if exist "%EXE%" (
  echo Starting WindexWD.exe ...
  start "" "%EXE%"
  exit /b 0
)

if not exist "%HTML%" (
  echo ERROR: Missing %HTML%
  echo Re-download WindexWD-Prototype-Test.zip and extract again.
  echo Or run CHECK.bat
  pause
  exit /b 1
)

echo No WindexWD.exe — starting local server on %URL%
echo (Do NOT double-click index.html — license needs http://)
echo To build EXE later: BUILD-EXE.bat
echo.

powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0SERVE-LOCAL.ps1" -Port %PORT%
