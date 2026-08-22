@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "EXE=%~dp0WindexWD-Prototype-Test\WindexWD.exe"
if exist "%EXE%" (
  echo Starting Windex WD EXE...
  start "" "%EXE%"
  exit /b 0
)
set "APP=%~dp0WindexWD-Prototype-Test\app\index.html"
if not exist "%APP%" set "APP=%~dp0app\index.html"
if not exist "%APP%" (
  echo ERROR: WindexWD.exe not found. Run WindexWD-Prototype-Test\BUILD-EXE.bat first.
  pause
  exit /b 1
)
echo EXE missing — opening app HTML. Build EXE: WindexWD-Prototype-Test\BUILD-EXE.bat
where msedge >nul 2>&1 && start "" msedge --app="%APP%" && exit /b 0
start "" "%APP%"
