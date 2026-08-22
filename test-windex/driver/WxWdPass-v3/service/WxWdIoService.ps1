#Requires -Version 5.1
<#
  WxWdPass v3 Phase 1 — user-mode I/O helper (Win10/11 x64)
  Actions: Scan | Lock | Unlock | Status
#>
param(
  [ValidateSet('Scan','Lock','Unlock','Status')]
  [string]$Action = 'Scan',
  [int]$DiskNumber = -1,
  [string]$DataRoot = 'D:\test windex'
)

$StateFile = Join-Path $DataRoot 'driver\wxwd-state.json'

function Get-BootDisk {
  $p = Get-Partition -ErrorAction SilentlyContinue | Where-Object IsBoot | Select-Object -First 1
  if ($p) { return [int]$p.DiskNumber }
  return -1
}

function Write-Scan {
  $boot = Get-BootDisk
  Write-Host "Boot disk (protected): $boot"
  Get-Disk | ForEach-Object {
    $tag = if ($_.Number -eq $boot) { '[BOOT]' } elseif ($_.Number -eq $DiskNumber) { '[LOCKED?]' } else { '      ' }
    Write-Host ("$tag Disk {0}: {1}  {2}  Offline={3}" -f $_.Number, $_.FriendlyName, $_.BusType, $_.IsOffline)
  }
}

function Lock-Disk {
  param([int]$N)
  $boot = Get-BootDisk
  if ($N -lt 0) { throw 'Specify -DiskNumber' }
  if ($N -eq $boot) { throw "Refusing to lock boot disk $N" }
  Set-Disk -Number $N -IsOffline $true
  $vols = Get-Partition -DiskNumber $N -ErrorAction SilentlyContinue | Get-Volume -ErrorAction SilentlyContinue
  foreach ($v in $vols) {
    if ($v.DriveLetter) {
      Write-Host "Dismounting $($v.DriveLetter):"
      $dl = "$($v.DriveLetter):"
      mountvol $dl /D 2>$null
    }
  }
  $state = @{
    lockedDisk = $N
    lockedAt = (Get-Date).ToString('o')
    method = 'phase1-offline'
  }
  if (Test-Path $StateFile) {
    $existing = Get-Content $StateFile -Raw | ConvertFrom-Json
    $merged = @{}
    $existing.PSObject.Properties | ForEach-Object { $merged[$_.Name] = $_.Value }
    $merged['lock'] = $state
    $merged | ConvertTo-Json -Depth 6 | Set-Content -Encoding UTF8 $StateFile
  }
  Write-Host "[OK] Disk $N offline — Windows should not mount volumes"
}

function Unlock-Disk {
  param([int]$N)
  if ($N -lt 0) { throw 'Specify -DiskNumber' }
  Set-Disk -Number $N -IsOffline $false
  Write-Host "[OK] Disk $N online again"
}

switch ($Action) {
  'Scan'   { Write-Scan }
  'Lock'   { Lock-Disk -N $DiskNumber }
  'Unlock' { Unlock-Disk -N $DiskNumber }
  'Status' {
    if (Test-Path $StateFile) { Get-Content $StateFile -Raw } else { Write-Host 'No wxwd-state.json' }
  }
}
