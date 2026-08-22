@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  Windex WD — APPLY saved paths to D:\test windex
echo  =================================================
echo.

set "APP=D:\test windex\WindexWD-Prototype-Test"
set "DATA=D:\test windex"

if /i not "%~dp0"==%APP%\ (
  echo Note: running from %~dp0
  echo       saved path is %APP%
)

mkdir "%DATA%" 2>nul
mkdir "%DATA%\FW" 2>nul
mkdir "%DATA%\Backup" 2>nul

(
echo {
echo   "appRoot": "D:\\test windex\\WindexWD-Prototype-Test",
echo   "projectRoot": "D:\\test windex",
echo   "packRoot": "D:\\test windex\\FW",
echo   "backupRoot": "D:\\test windex\\Backup"
echo }
) > "%~dp0resources\app\project-path.json"

echo [OK] project-path.json written
echo [OK] %DATA%\FW
echo [OK] %DATA%\Backup
echo.
echo Run RUN.bat to start.
pause
