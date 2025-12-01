# PowerShell Script to Create Windows Task Scheduler Entry
# Run as Administrator to auto-start services on system boot

# Define paths
$taskName = "Inventory Tracker Auto Start"
$batFilePath = "d:\xampp\htdocs\inventorytracker\startup_services.bat"
$taskDescription = "Automatically start Inventory Tracker services on system startup"

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")

if (-not $isAdmin) {
    Write-Host "ERROR: This script must be run as Administrator!" -ForegroundColor Red
    Write-Host "Please right-click PowerShell and select 'Run as administrator'" -ForegroundColor Yellow
    pause
    exit
}

# Check if file exists
if (-not (Test-Path $batFilePath)) {
    Write-Host "ERROR: Batch file not found at $batFilePath" -ForegroundColor Red
    pause
    exit
}

try {
    # Check if task already exists
    $existingTask = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    
    if ($existingTask) {
        Write-Host "Task already exists. Updating..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    }

    # Create action - run the batch file
    $action = New-ScheduledTaskAction -Execute "cmd.exe" `
        -Argument "/c `"$batFilePath`"" `
        -WorkingDirectory "d:\xampp\htdocs\inventorytracker"

    # Create trigger - on system startup with 10 second delay
    $trigger = New-ScheduledTaskTrigger -AtStartup
    $trigger.Delay = "PT10S"  # 10 second delay to allow system to fully boot

    # Create settings
    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -RunOnlyIfNetworkAvailable:$false `
        -MultipleInstances IgnoreNew

    # Create and register the task
    $principal = New-ScheduledTaskPrincipal -UserID "NT AUTHORITY\SYSTEM" -LogonType ServiceAccount -RunLevel Highest
    
    Register-ScheduledTask -TaskName $taskName `
        -Action $action `
        -Trigger $trigger `
        -Settings $settings `
        -Principal $principal `
        -Description $taskDescription `
        -Force

    Write-Host "SUCCESS: Task Scheduler entry created!" -ForegroundColor Green
    Write-Host "`nTask Details:" -ForegroundColor Cyan
    Write-Host "  Task Name: $taskName" -ForegroundColor Yellow
    Write-Host "  Trigger: On system startup (with 10 second delay)" -ForegroundColor Yellow
    Write-Host "  Batch File: $batFilePath" -ForegroundColor Yellow
    Write-Host "`nServices will auto-start on next system boot!" -ForegroundColor Green
    Write-Host "`nTo remove this task later, run:" -ForegroundColor Cyan
    Write-Host "  Unregister-ScheduledTask -TaskName '$taskName' -Confirm:`$false" -ForegroundColor Gray

}
catch {
    Write-Host "ERROR: Failed to create task scheduler entry" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    pause
    exit
}

pause
