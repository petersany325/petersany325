@echo off
chcp 65001 >nul
setlocal EnableDelayedExpansion
cd /d "%~dp0"

echo.
echo  Windex WD — UPDATE LOCAL (no internet)
echo  ======================================
echo  Target: %~dp0
echo.

set "DEST=%~dp0resources\app"
set "SRC=%~dp0update"

if not exist "%SRC%\index.html" (
  echo [FAIL] Folder not found: %SRC%
  echo        Put latest files in update\ or re-copy full pack.
  pause
  exit /b 1
)

echo Copying update\ --^> resources\app\ ...
echo.

for %%F in (app.js index.html license.js styles.css project-path.json family-fw-reference.json license-keygen.html package.json) do (
  if exist "%SRC%\%%F" (
    copy /Y "%SRC%\%%F" "%DEST%\%%F" >nul
    echo [OK] %%F
  )
)

if exist "%SRC%\assets" (
  if not exist "%DEST%\assets" mkdir "%DEST%\assets"
  xcopy /E /Y /Q "%SRC%\assets\*" "%DEST%\assets\" >nul
  echo [OK] assets\
)

if exist "%SRC%\electron" (
  if not exist "%DEST%\electron" mkdir "%DEST%\electron"
  xcopy /E /Y /Q "%SRC%\electron\*" "%DEST%\electron\" >nul
  echo [OK] electron\
)

copy /Y "%~dp0APP-PATH.txt" "%~dp0APP-PATH.txt" >nul 2>&1
copy /Y "%~dp0CHECK.bat" "%~dp0CHECK.bat" >nul 2>&1
copy /Y "%~dp0RUN.bat" "%~dp0RUN.bat" >nul 2>&1

echo.
echo [OK] Local update done.
echo      Run CHECK.bat then RUN.bat
echo.
pause
