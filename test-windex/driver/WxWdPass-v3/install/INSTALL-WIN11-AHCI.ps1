#Requires -Version 5.1
#Requires -RunAsAdministrator
<#
.SYNOPSIS
  WxWdPass v3 — Phase 1 install for Windows 10/11 x64 (AHCI/SATA lab path)
.DESCRIPTION
  - Verifies x64 + build >= 19041
  - Enumerates SATA/AHCI disks (boot disk protected)
  - Installs Phase 1 helper (WxWdIoService.ps1) — SCSI/ATA pass-through path
  - Prepares pnputil install when WxWdPassAhci.sys is present (Phase 2)
  - Writes state to D:\test windex\driver\wxwd-state.json
#>
param(
  [string]$DataRoot = 'D:\test windex',
  [switch]$Uninstall
)

$ErrorActionPreference = 'Stop'
$InstallRoot = Join-Path $DataRoot 'driver\WxWdPass-v3'
$StateFile = Join-Path $DataRoot 'driver\wxwd-state.json'
$ServiceDir = Join-Path $InstallRoot 'service'
$InfPath = Join-Path $InstallRoot 'install\inf\wxwdahci.inf'
$SysPath = Join-Path $InstallRoot 'bin\WxWdPassAhci.sys'

function Write-Title($t) { Write-Host "`n=== $t ===" -ForegroundColor Cyan }

function Test-Platform {
  if (-not [Environment]::Is64BitOperatingSystem) {
    throw 'WxWdPass v3 requires Windows x64 (64-bit).'
  }
  $os = Get-CimInstance Win32_OperatingSystem
  $build = [int]$os.BuildNumber
  if ($build -lt 19041) {
    throw "Windows 10 build 19041+ or Windows 11 required (current build: $build)."
  }
  $caption = $os.Caption
  Write-Host "[OK] $caption x64 (build $build)"
}

function Get-BootDiskNumber {
  $boot = Get-Partition -ErrorAction SilentlyContinue | Where-Object { $_.IsBoot -eq $true } | Select-Object -First 1
  if (-not $boot) { return -1 }
  $disk = Get-Partition -DiskNumber $boot.DiskNumber -ErrorAction SilentlyContinue | Select-Object -First 1
  return [int]$boot.DiskNumber
}

function Get-LabDisks {
  param([int]$BootDisk)
  $disks = Get-Disk -ErrorAction SilentlyContinue | Where-Object {
    $_.Number -ne $BootDisk -and $_.BusType -in @('SATA','RAID','ATA')
  }
  return @($disks)
}

function Get-AhciControllers {
  $devs = @()
  foreach ($cls in @('SCSIAdapter','HDC')) {
    $devs += Get-PnpDevice -Class $cls -ErrorAction SilentlyContinue |
      Where-Object { $_.Status -eq 'OK' -and $_.FriendlyName -match 'SATA|AHCI|AHCI Controller|Standard SATA' }
  }
  return $devs | Sort-Object InstanceId -Unique
}

function Install-Phase1 {
  Write-Title 'WxWdPass v3 — Phase 1 (Win11 AHCI lab)'
  Test-Platform

  New-Item -ItemType Directory -Force -Path $DataRoot, (Join-Path $DataRoot 'driver'), $InstallRoot, $ServiceDir | Out-Null

  $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
  $srcService = Join-Path $scriptDir '..\service\WxWdIoService.ps1'
  if (Test-Path $srcService) {
    Copy-Item -Force $srcService (Join-Path $ServiceDir 'WxWdIoService.ps1')
    Write-Host '[OK] WxWdIoService.ps1 installed'
  }

  $boot = Get-BootDiskNumber
  Write-Host "[OK] Boot disk number: $boot (protected — never lock)"

  $ctrl = Get-AhciControllers
  Write-Host "[OK] AHCI/SATA controllers found: $($ctrl.Count)"
  foreach ($c in $ctrl) {
    Write-Host "     - $($c.FriendlyName)"
  }

  $lab = Get-LabDisks -BootDisk $boot
  Write-Host "[OK] Candidate DUT disks (non-boot SATA): $($lab.Count)"
  foreach ($d in $lab) {
    Write-Host ("     - Disk {0}: {1} GB  {2}  {3}" -f $d.Number, [math]::Round($d.Size/1GB,1), $d.FriendlyName, $d.SerialNumber)
  }

  $state = @{
    version = '3.0.0-phase1'
    installedAt = (Get-Date).ToString('o')
    platform = (Get-CimInstance Win32_OperatingSystem).Caption
    build = (Get-CimInstance Win32_OperatingSystem).BuildNumber
    dataRoot = $DataRoot
    bootDisk = $boot
    phase = 1
    kernelDriver = (Test-Path $SysPath)
    controllers = @($ctrl | ForEach-Object { @{ name = $_.FriendlyName; id = $_.InstanceId } })
    disks = @($lab | ForEach-Object {
      @{
        number = $_.Number
        serial = $_.SerialNumber
        model = $_.FriendlyName
        sizeGb = [math]::Round($_.Size / 1GB, 1)
        busType = $_.BusType.ToString()
      }
    })
  }
  $state | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 $StateFile
  Write-Host "[OK] State written: $StateFile"

  if (Test-Path $SysPath) {
    Write-Title 'Phase 2 — kernel driver (pnputil)'
    pnputil /add-driver $InfPath /install
    Write-Host '[OK] WxWdPassAhci.sys install attempted (requires signed driver)'
  } else {
    Write-Host '[--] WxWdPassAhci.sys not in bin\ — Phase 1 only (ATA pass-through helper)'
    Write-Host '     Full port lock needs Phase 2 kernel build + WHQL signing.'
  }

  Write-Title 'Done'
  Write-Host 'Next: open Windex WD → Detect → Lock port on DUT disk'
  Write-Host "Helper: powershell -File `"$ServiceDir\WxWdIoService.ps1`" -Action Scan"
}

function Uninstall-Phase1 {
  Write-Title 'WxWdPass v3 — uninstall Phase 1'
  if (Test-Path $StateFile) { Remove-Item -Force $StateFile }
  Write-Host '[OK] Removed state file'
  if (Test-Path $SysPath) {
    pnputil /delete-driver wxwdahci.inf /uninstall /force 2>$null
  }
  Write-Host '[OK] Uninstall complete (reboot if kernel driver was loaded)'
}

if ($Uninstall) { Uninstall-Phase1 } else { Install-Phase1 }
