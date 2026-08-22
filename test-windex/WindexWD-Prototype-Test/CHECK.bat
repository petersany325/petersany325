@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo Checking WindexWD-Prototype-Test ...
echo.
if exist "%~dp0WindexWD.exe" (echo [OK] WindexWD.exe) else (echo [--] WindexWD.exe missing — use RUN.bat or BUILD-EXE.bat)
if exist "%~dp0resources\app\index.html" (echo [OK] resources\app\index.html) else (echo [FAIL] resources\app\index.html MISSING)
if exist "%~dp0resources\app\app.js" (echo [OK] app.js) else (echo [FAIL] app.js MISSING)
if exist "%~dp0resources\app\license.js" (echo [OK] license.js) else (echo [FAIL] license.js MISSING)
if exist "%~dp0resources\app\electron\main.js" (echo [OK] electron\main.js) else (echo [FAIL] electron MISSING)
echo.
echo Run:  RUN.bat  or  START-WINDEX-WD.bat
echo Build EXE:  BUILD-EXE.bat
pause
