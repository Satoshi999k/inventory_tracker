@echo off
REM Direct Windows Task Scheduler Setup for Inventory Tracker Auto-Start
REM This must be run as Administrator

setlocal enabledelayedexpansion

echo.
echo ====================================
echo Inventory Tracker - Auto Start Setup
echo ====================================
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script must be run as Administrator!
    echo.
    echo Please follow these steps:
    echo 1. Right-click this file
    echo 2. Select "Run as administrator"
    echo.
    pause
    exit /b 1
)

set TASK_NAME=Inventory Tracker Auto Start
set BAT_FILE=d:\xampp\htdocs\inventorytracker\startup_services.bat

REM Delete existing task if it exists
echo Checking for existing task...
schtasks /query /tn "%TASK_NAME%" >nul 2>&1
if %errorLevel% equ 0 (
    echo Removing existing task...
    schtasks /delete /tn "%TASK_NAME%" /f >nul 2>&1
)

REM Create new task
echo Creating scheduled task...
schtasks /create /tn "%TASK_NAME%" /tr "%BAT_FILE%" /sc onstart /rl highest /f /delay 0000:10

REM Check if task was created successfully
if %errorLevel% equ 0 (
    echo.
    echo ====================================
    echo SUCCESS! Auto-start enabled!
    echo ====================================
    echo.
    echo Services will start automatically on next system boot.
    echo.
    echo Task Name: %TASK_NAME%
    echo Batch File: %BAT_FILE%
    echo Trigger: On system startup (10 second delay)
    echo.
    echo You can now close this window.
    echo.
) else (
    echo.
    echo ERROR: Failed to create task scheduler entry
    echo.
)

pause
