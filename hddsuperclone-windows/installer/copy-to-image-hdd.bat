@echo off
setlocal EnableExtensions
rem Copy the HDDSuperClone Windows tree onto D:\HDDSuperClone\CLONE IMAGE HDD-LAND
set "DEST=D:\HDDSuperClone\CLONE IMAGE HDD-LAND"
set "SRC=%~dp0"
if "%SRC:~-1%"=="\" set "SRC=%SRC:~0,-1%"
if exist "%SRC%\..\CMakeLists.txt" if exist "%SRC%\hddsuperclone.nsi" set "SRC=%SRC%\.."

echo Source:      %SRC%
echo Destination: %DEST%
mkdir "D:\HDDSuperClone" 2>nul
mkdir "%DEST%" 2>nul
if not exist "%DEST%" (
  echo Could not create "%DEST%"
  exit /b 1
)
if /I "%SRC%"=="%DEST%" (
  echo Already in the destination folder.
  exit /b 0
)
xcopy /E /I /Y /Q "%SRC%\*" "%DEST%\"
if errorlevel 1 (
  echo Copy failed.
  exit /b 1
)
echo Done.
echo Run: "%DEST%\hddsuperclone-windows.exe"
endlocal
