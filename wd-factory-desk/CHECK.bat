@echo off
chcp 65001 >nul
cd /d "%~dp0"
echo.
echo  Windex WD — CHECK
echo  =================
echo  Expected app path: D:\test windex\WindexWD-Prototype-Test
echo.

set OK=0
set FAIL=0

if /i "%~dp0"==D:\test windex\WindexWD-Prototype-Test\ (
  echo [OK] Running from saved install path
  set /a OK+=1
) else (
  echo [--] Current folder: %~dp0
  echo      Recommended:   D:\test windex\WindexWD-Prototype-Test\
)

if exist "%~dp0RUN.bat" (echo [OK] RUN.bat) else (echo [FAIL] RUN.bat & set /a FAIL+=1)
if exist "%~dp0SERVE-LOCAL.ps1" (echo [OK] SERVE-LOCAL.ps1) else (echo [FAIL] SERVE-LOCAL.ps1 & set /a FAIL+=1)
if exist "%~dp0resources\app\index.html" (echo [OK] index.html) else (echo [FAIL] index.html & set /a FAIL+=1)
if exist "%~dp0resources\app\app.js" (echo [OK] app.js) else (echo [FAIL] app.js & set /a FAIL+=1)
if exist "%~dp0resources\app\license.js" (echo [OK] license.js) else (echo [FAIL] license.js & set /a FAIL+=1)
if exist "%~dp0resources\app\project-path.json" (echo [OK] project-path.json) else (echo [FAIL] project-path.json & set /a FAIL+=1)
if exist "%~dp0resources\app\electron\main.js" (echo [OK] electron\main.js) else (echo [--] electron optional)
if exist "%~dp0WindexWD.exe" (echo [OK] WindexWD.exe) else (echo [--] WindexWD.exe — RUN.bat works without it)

echo.
echo Data folders (parent of app):
if exist "D:\test windex\FW" (echo [OK] D:\test windex\FW) else (echo [--] D:\test windex\FW — run SETUP-LAB.bat)
if exist "D:\test windex\Backup" (echo [OK] D:\test windex\Backup) else (echo [--] D:\test windex\Backup — run SETUP-LAB.bat)

echo.
if %FAIL% GTR 0 (
  echo RESULT: FAIL — re-download zip or run UPDATE.bat
) else (
  echo RESULT: OK — run RUN.bat
)
echo.
pause
