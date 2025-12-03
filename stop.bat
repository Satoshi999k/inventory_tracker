@echo off
REM Inventory Tracker - Stop All Services

color 0C
title Stopping Inventory Tracker Services...

echo.
echo ========================================
echo   Stopping All Services...
echo ========================================
echo.

REM Kill all PHP processes
echo Stopping all PHP servers...
taskkill /F /IM php.exe >nul 2>&1

if %ERRORLEVEL% EQU 0 (
    echo.
    echo Successfully stopped all services!
) else (
    echo.
    echo No services were running.
)

echo.
echo ========================================
echo   All services have been stopped
echo ========================================
echo.
pause
