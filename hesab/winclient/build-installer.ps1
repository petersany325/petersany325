# Build self-contained HesabWin.exe + Inno Setup installer (run on Windows)
# Usage:
#   powershell -ExecutionPolicy Bypass -File .\build-installer.ps1
# Optional:
#   -SkipInstaller   only publish portable folder/zip

param(
  [switch]$SkipInstaller,
  [string]$Configuration = "Release",
  [string]$Version = "1.0.0"
)

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $MyInvocation.MyCommand.Path
$Project = Join-Path $Root "HesabWin\HesabWin.csproj"
$PublishDir = Join-Path $Root "artifacts\publish"
$Artifacts = Join-Path $Root "artifacts"

Write-Host "==> Publishing self-contained win-x64 ($Configuration)…" -ForegroundColor Cyan
if (Test-Path $PublishDir) { Remove-Item $PublishDir -Recurse -Force }
New-Item -ItemType Directory -Force -Path $Artifacts | Out-Null

dotnet publish $Project `
  -c $Configuration `
  -r win-x64 `
  --self-contained true `
  -p:PublishSingleFile=true `
  -p:IncludeNativeLibrariesForSelfExtract=true `
  -p:EnableCompressionInSingleFile=true `
  -p:Version=$Version `
  -o $PublishDir

if ($LASTEXITCODE -ne 0) { throw "dotnet publish failed" }

$zip = Join-Path $Artifacts "Hesab-HDD-Portable-$Version-win-x64.zip"
if (Test-Path $zip) { Remove-Item $zip -Force }
Compress-Archive -Path (Join-Path $PublishDir "*") -DestinationPath $zip -Force
Write-Host "==> Portable zip: $zip" -ForegroundColor Green

if ($SkipInstaller) {
  Write-Host "Skipped Inno Setup (-SkipInstaller)." -ForegroundColor Yellow
  exit 0
}

$iscc = @(
  "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe",
  "$env:LocalAppData\Programs\Inno Setup 6\ISCC.exe",
  "C:\Program Files (x86)\Inno Setup 6\ISCC.exe"
) | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $iscc) {
  Write-Host "Inno Setup 6 not found — portable zip is ready. Install Inno Setup to build Setup.exe:" -ForegroundColor Yellow
  Write-Host "  https://jrsoftware.org/isdl.php"
  exit 0
}

$iss = Join-Path $Root "installer\HesabHDD.iss"
# Patch version in iss output name via define override
& $iscc "/DMyAppVersion=$Version" $iss
if ($LASTEXITCODE -ne 0) { throw "Inno Setup compile failed" }

Write-Host "==> Installer ready in $Artifacts" -ForegroundColor Green
Get-ChildItem $Artifacts -Filter "Hesab-HDD-*" | Format-Table Name, Length, LastWriteTime
