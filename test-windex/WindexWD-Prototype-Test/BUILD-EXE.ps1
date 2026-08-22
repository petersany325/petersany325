#Requires -Version 5.1
# Build WindexWD.exe on Windows (no Node required)
$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$AppDir = Join-Path $Root 'resources\app'
if (-not (Test-Path $AppDir)) { $AppDir = Join-Path $Root 'app' }
if (-not (Test-Path $AppDir)) {
  Write-Error "App folder not found (resources\app or app)"
}
$OutDir = $Root
$ElectronVer = '28.3.3'
$ZipUrl = "https://github.com/electron/electron/releases/download/v$ElectronVer/electron-v$ElectronVer-win32-x64.zip"
$ZipPath = Join-Path $env:TEMP "electron-v$ElectronVer-win32-x64.zip"

Write-Host "Windex WD — building EXE in $OutDir"
if (-not (Test-Path $ZipPath)) {
  Write-Host "Downloading Electron $ElectronVer ..."
  Invoke-WebRequest -Uri $ZipUrl -OutFile $ZipPath -UseBasicParsing
}
$Temp = Join-Path $env:TEMP "windexwd-electron-build"
if (Test-Path $Temp) { Remove-Item $Temp -Recurse -Force }
Expand-Archive -Path $ZipPath -DestinationPath $Temp -Force
Get-ChildItem $Temp | ForEach-Object {
  Copy-Item $_.FullName -Destination $OutDir -Recurse -Force
}
$ElectronExe = Join-Path $OutDir 'electron.exe'
$TargetExe = Join-Path $OutDir 'WindexWD.exe'
if (Test-Path $ElectronExe) { Move-Item -Force $ElectronExe $TargetExe }
$ResApp = Join-Path $OutDir 'resources\app'
if (Test-Path $ResApp) { Remove-Item $ResApp -Recurse -Force }
New-Item -ItemType Directory -Path $ResApp -Force | Out-Null
Copy-Item -Path (Join-Path $AppDir '*') -Destination $ResApp -Recurse -Force
Remove-Item (Join-Path $OutDir 'resources\default_app.asar') -ErrorAction SilentlyContinue
Write-Host "Done: $TargetExe"
Write-Host "Run WindexWD.exe or START-WINDEX-WD.bat"
