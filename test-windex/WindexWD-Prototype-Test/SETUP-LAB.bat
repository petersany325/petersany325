@echo off
chcp 65001 >nul
echo.
echo  Windex WD — SETUP LAB folders
echo  =============================
echo  Creates FW and Backup under D:\test windex
echo.

set "DATA=D:\test windex"
if not exist "%DATA%" mkdir "%DATA%"
mkdir "%DATA%\FW" 2>nul
mkdir "%DATA%\FW\2.5" 2>nul
mkdir "%DATA%\FW\3.5" 2>nul
mkdir "%DATA%\Backup" 2>nul

echo [OK] %DATA%\FW
echo [OK] %DATA%\Backup
echo.
echo App should be at: %DATA%\WindexWD-Prototype-Test
echo Run: %DATA%\WindexWD-Prototype-Test\RUN.bat
echo.
pause
