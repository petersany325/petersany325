# HDD Land Remote Soft - Silent Windows Install Script
# Run as Administrator on target PCs (Win 7 / 10 / 11)

param(
    [string]$InstallerPath = ".\HDDLandRemote.exe",
    [string]$InstallDir = "$env:ProgramFiles\HDD Land Remote Soft",
    [switch]$Silent
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path $InstallerPath)) {
    Write-Error "Installer not found: $InstallerPath"
}

Write-Host "Installing HDD Land Remote Soft..."

# RustDesk/HDDLandRemote supports --silent-install
& $InstallerPath --silent-install

Write-Host "Done. Service: HDD Land Remote Soft"
Write-Host "Config path: $env:APPDATA\HDD Land Remote Soft\config\"

# Optional: import server config (uncomment and set your relay)
# $config = @"
# host = your-relay.hdd-land.local
# key = YOUR_PUBLIC_KEY
# "@
# $configDir = "$env:APPDATA\HDD Land Remote Soft\config"
# New-Item -ItemType Directory -Force -Path $configDir | Out-Null
# Set-Content -Path "$configDir\HDD Land Remote Soft.toml" -Value $config
