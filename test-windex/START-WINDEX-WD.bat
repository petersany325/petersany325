@echo off
chcp 65001 >nul
cd /d "%~dp0"
set "SUB=%~dp0WindexWD-Prototype-Test"
if exist "%SUB%\RUN.bat" (
  call "%SUB%\RUN.bat"
  exit /b %ERRORLEVEL%
)
echo ERROR: WindexWD-Prototype-Test\RUN.bat not found
pause
exit /b 1
