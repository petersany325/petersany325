@echo off
chcp 65001 >nul
cd /d "%~dp0"

if exist "%~dp0WindexWD.exe" (
  start "" "%~dp0WindexWD.exe"
  exit /b 0
)

set "PORT=8765"
set "URL=http://127.0.0.1:%PORT%/"

if not exist "%~dp0resources\app\index.html" (
  echo ERROR: resources\app\index.html missing. Run CHECK.bat
  pause
  exit /b 1
)

echo Opening Windex WD at %URL%
start "" powershell -NoProfile -WindowStyle Minimized -ExecutionPolicy Bypass -File "%~dp0SERVE-LOCAL.ps1" -Port %PORT%
timeout /t 2 /nobreak >nul

where msedge >nul 2>&1
if %ERRORLEVEL%==0 (
  start "" msedge --app="%URL%" --disable-features=TranslateUI
  exit /b 0
)
if exist "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" (
  start "" "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" --app="%URL%"
  exit /b 0
)
if exist "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" (
  start "" "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" --app="%URL%"
  exit /b 0
)
if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" (
  start "" "%LocalAppData%\Google\Chrome\Application\chrome.exe" --app="%URL%"
  exit /b 0
)

start "" "%URL%"
