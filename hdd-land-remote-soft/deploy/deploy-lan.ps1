# Deploy HDD Land Remote Soft to multiple PCs on LAN
# Usage: .\deploy-lan.ps1 -ComputerList pcs.txt -Installer \\server\share\HDDLandRemote.exe

param(
    [Parameter(Mandatory = $true)]
    [string]$ComputerList,

    [Parameter(Mandatory = $true)]
    [string]$Installer
)

$computers = Get-Content $ComputerList | Where-Object { $_ -and -not $_.StartsWith("#") }

foreach ($pc in $computers) {
    Write-Host "Deploying to $pc ..."
    try {
        $dest = "\\$pc\C$\Temp\HDDLandRemote.exe"
        Copy-Item $Installer $dest -Force
        Invoke-Command -ComputerName $pc -ScriptBlock {
            & "C:\Temp\HDDLandRemote.exe" --silent-install
        }
        Write-Host "  OK: $pc"
    }
    catch {
        Write-Warning "  FAILED: $pc - $_"
    }
}
