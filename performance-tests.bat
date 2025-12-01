@echo off
REM Performance Testing Script for Windows
REM Uses Apache Bench (ab) which comes with Apache
REM 
REM Requirements: Apache installed with ab.exe in PATH
REM Download: https://www.apachehaus.com/cgi-bin/download.plx

echo.
echo 🚀 PERFORMANCE TESTING SUITE (Windows)
echo ==========================================
echo.

set API_URL=http://localhost:8000
set RESULTS_FILE=performance-results.txt

REM Check if ab.exe exists
where ab.exe >nul 2>nul
if %ERRORLEVEL% NEQ 0 (
    echo ⚠️  Apache Bench (ab.exe) not found in PATH
    echo.
    echo Solution 1: Install Apache with ab.exe
    echo   Download from: https://www.apachehaus.com/
    echo.
    echo Solution 2: Add Apache bin to PATH
    echo   setx PATH "%%PATH%%;C:\Apache24\bin"
    echo.
    exit /b 1
)

echo 📊 Test 1: API Gateway Health Check
echo -----------
curl -s %API_URL%/health
echo.
echo.

echo 📊 Test 2: GET /products (Baseline)
echo -----------
echo Sending 100 requests with 10 concurrent connections...
ab.exe -n 100 -c 10 -q %API_URL%/products | findstr "Requests per second" /c:"Mean time per request" /c:"Failed requests"
echo.

echo 📊 Test 3: GET /inventory (Baseline)
echo -----------
echo Sending 100 requests with 10 concurrent connections...
ab.exe -n 100 -c 10 -q %API_URL%/inventory | findstr "Requests per second" /c:"Mean time per request" /c:"Failed requests"
echo.

echo 📊 Test 4: High Concurrency Test
echo -----------
echo Sending 500 requests with 50 concurrent connections...
ab.exe -n 500 -c 50 -q %API_URL%/products | findstr "Requests per second" /c:"Mean time per request" /c:"Failed requests"
echo.

echo 📊 Test 5: Sustained Load Test
echo -----------
echo Running 30-second sustained load with 20 concurrent connections...
ab.exe -t 30 -c 20 -q %API_URL%/inventory | findstr "Requests per second" /c:"Mean time per request" /c:"Failed requests"
echo.

echo ✅ Performance tests complete
echo Results saved to: %RESULTS_FILE%
echo.
pause
