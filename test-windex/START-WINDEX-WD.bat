@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "APP=%~dp0app\index.html"
echo.
echo  Windex WD — D:\test windex
echo  ==========================
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
start "" "%APP%"
