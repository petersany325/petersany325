@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  Build Windex WD.exe  (run this ON Windows with Node.js installed)
echo  ================================================================
echo.
where node >nul 2>&1
if errorlevel 1 (
  echo Install Node.js LTS from https://nodejs.org then re-run.
  pause
  exit /b 1
)
call npm install
call npx electron-packager . "Windex WD" --platform=win32 --arch=x64 --out=dist-win --overwrite --asar --prune --icon=assets\app-icon.png
echo.
echo  Output: dist-win\Windex WD-win32-x64\Windex WD.exe
echo  Copy that folder to D:\test windex\ and run the EXE.
echo.
pause
