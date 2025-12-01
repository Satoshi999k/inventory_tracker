' Inventory Tracker Auto-Start Script
' This script runs all services when Windows boots
' No console window will appear

Set objShell = CreateObject("WScript.Shell")
strBatPath = "d:\xampp\htdocs\inventorytracker\AUTO_START_SERVICES.bat"

' Run the batch file hidden (0 = hidden, False = wait for completion)
objShell.Run strBatPath, 0, False

' Optionally add a small delay and show a notification
WScript.Sleep 2000
