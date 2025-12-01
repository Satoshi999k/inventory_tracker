# PowerShell Script to Enable Auto-Start for Inventory Tracker
# Run this script as Administrator

# Define paths
$batFilePath = "d:\xampp\htdocs\inventorytracker\AUTO_START_SERVICES.bat"
$registryPath = "HKCU:\Software\Microsoft\Windows\CurrentVersion\Run"
$registryName = "InventoryTrackerServices"

# Check if file exists
if (-Not (Test-Path $batFilePath)) {
    Write-Host "ERROR: Batch file not found at $batFilePath" -ForegroundColor Red
    exit
}

# Create registry entry for auto-start
try {
    Set-ItemProperty -Path $registryPath -Name $registryName -Value $batFilePath
    Write-Host "SUCCESS: Auto-start enabled!" -ForegroundColor Green
    Write-Host "Services will start automatically on next boot" -ForegroundColor Green
    Write-Host "Registry path: $registryPath" -ForegroundColor Yellow
    Write-Host "Entry: $registryName" -ForegroundColor Yellow
}
catch {
    Write-Host "ERROR: Failed to set registry entry" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    exit
}

# Verify the entry was created
$registryEntry = Get-ItemProperty -Path $registryPath -Name $registryName -ErrorAction SilentlyContinue
if ($registryEntry) {
    Write-Host "`nVerified: Registry entry created successfully" -ForegroundColor Green
}
