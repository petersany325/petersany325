#Requires -Version 5.1
# Build WindexWD.exe on Windows (no Node required)
$ErrorActionPreference = 'Stop'
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$AppSrc = Join-Path $Root 'resources\app'
if (-not (Test-Path $AppSrc)) { $AppSrc = Join-Path $Root 'app' }
if (-not (Test-Path $AppSrc)) {
  Write-Error "App folder not found: resources\app"
}

# IMPORTANT: stage app BEFORE deleting resources\app
$StageApp = Join-Path $env:TEMP "windexwd-app-stage"
if (Test-Path $StageApp) { Remove-Item $StageApp -Recurse -Force }
Copy-Item -Path $AppSrc -Destination $StageApp -Recurse -Force

$OutDir = $Root
$ElectronVer = '28.3.3'
$ZipUrl = "https://github.com/electron/electron/releases/download/v$ElectronVer/electron-v$ElectronVer-win32-x64.zip"
$ZipPath = Join-Path $env:TEMP "electron-v$ElectronVer-win32-x64.zip"

Write-Host "Windex WD — building EXE in $OutDir"
if (-not (Test-Path $ZipPath)) {
  Write-Host "Downloading Electron $ElectronVer ..."
  [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
  Invoke-WebRequest -Uri $ZipUrl -OutFile $ZipPath -UseBasicParsing
}

$Temp = Join-Path $env:TEMP "windexwd-electron-build"
if (Test-Path $Temp) { Remove-Item $Temp -Recurse -Force }
New-Item -ItemType Directory -Path $Temp -Force | Out-Null

# electron zip is a zip — Expand-Archive works on Win10+
Add-Type -AssemblyName System.IO.Compression.FileSystem
[System.IO.Compression.ZipFile]::ExtractToDirectory($ZipPath, $Temp)

Get-ChildItem $Temp | ForEach-Object {
  Copy-Item $_.FullName -Destination $OutDir -Recurse -Force
}

$ElectronExe = Join-Path $OutDir 'electron.exe'
$TargetExe = Join-Path $OutDir 'WindexWD.exe'
if (Test-Path $ElectronExe) {
  if (Test-Path $TargetExe) { Remove-Item $TargetExe -Force }
  Move-Item -Force $ElectronExe $TargetExe
}

$ResApp = Join-Path $OutDir 'resources\app'
if (Test-Path $ResApp) { Remove-Item $ResApp -Recurse -Force }
Copy-Item -Path $StageApp -Destination $ResApp -Recurse -Force
Remove-Item (Join-Path $OutDir 'resources\default_app.asar') -ErrorAction SilentlyContinue

Write-Host ""
Write-Host "OK: $TargetExe"
Write-Host "Run: WindexWD.exe  or  START-WINDEX-WD.bat"
