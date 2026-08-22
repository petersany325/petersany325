@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "APP=%~dp0app\index.html"
if not exist "%APP%" set "APP=%~dp0index.html"
if not exist "%APP%" (
  echo ERROR: index.html not found.
  pause
  exit /b 1
)

echo.
echo  Windex WD — HamGap Desktop
echo  ==========================
echo  Starting as Windows app window...
echo  License: File menu → License Settings  (Ctrl+L in Electron build)
echo.

REM Prefer Microsoft Edge app mode (no browser tabs/chrome)
where msedge >nul 2>&1
if %ERRORLEVEL%==0 (
  start "" msedge --app="%APP%" --disable-features=TranslateUI
  exit /b 0
)

if exist "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" (
  start "" "%ProgramFiles(x86)%\Microsoft\Edge\Application\msedge.exe" --app="%APP%"
  exit /b 0
)

if exist "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" (
  start "" "%ProgramFiles%\Microsoft\Edge\Application\msedge.exe" --app="%APP%"
  exit /b 0
)

REM Chrome app mode fallback
where chrome >nul 2>&1
if %ERRORLEVEL%==0 (
  start "" chrome --app="%APP%"
  exit /b 0
)

if exist "%ProgramFiles%\Google\Chrome\Application\chrome.exe" (
  start "" "%ProgramFiles%\Google\Chrome\Application\chrome.exe" --app="%APP%"
  exit /b 0
)

if exist "%LocalAppData%\Google\Chrome\Application\chrome.exe" (
  start "" "%LocalAppData%\Google\Chrome\Application\chrome.exe" --app="%APP%"
  exit /b 0
)

echo Edge/Chrome not found — opening in default browser.
start "" "%APP%"
