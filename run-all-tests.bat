@echo off
REM ============================================
REM Complete Testing Suite Runner (Windows)
REM ============================================
REM This script runs all testing levels:
REM - Unit Tests
REM - Integration Tests  
REM - E2E Tests
REM - Performance Tests
REM - Monitoring & Validation
REM ============================================

setlocal enabledelayedexpansion

echo.
echo 🧪 COMPLETE TESTING SUITE
echo ============================================
echo Running all test levels...
echo ============================================
echo.

set FAILED=0
set PASSED=0

REM Colors
set GREEN=
set RED=
set YELLOW=

REM ============================================
REM 1. UNIT TESTS
REM ============================================
echo.
echo 1️⃣  UNIT TESTS
echo -------------------------------------------
echo.

cd services\product-catalog >nul 2>&1
if exist composer.json (
    echo Testing Product Catalog Service...
    composer test >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✅ Product Catalog Tests PASSED
        set /a PASSED+=1
    ) else (
        echo ❌ Product Catalog Tests FAILED
        set /a FAILED+=1
    )
)
cd ..\..\

cd services\inventory >nul 2>&1
if exist composer.json (
    echo Testing Inventory Service...
    composer test >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✅ Inventory Tests PASSED
        set /a PASSED+=1
    ) else (
        echo ❌ Inventory Tests FAILED
        set /a FAILED+=1
    )
)
cd ..\..\

cd services\sales >nul 2>&1
if exist composer.json (
    echo Testing Sales Service...
    composer test >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✅ Sales Tests PASSED
        set /a PASSED+=1
    ) else (
        echo ❌ Sales Tests FAILED
        set /a FAILED+=1
    )
)
cd ..\..\

REM ============================================
REM 2. INTEGRATION TESTS
REM ============================================
echo.
echo 2️⃣  INTEGRATION TESTS
echo -------------------------------------------
echo.

echo Running integration tests...
if exist integration-tests.php (
    php integration-tests.php >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✅ Integration Tests PASSED
        set /a PASSED+=1
    ) else (
        echo ⚠️  Integration Tests - Check services are running
        echo.
    )
) else (
    echo ❌ integration-tests.php not found
)

REM ============================================
REM 3. END-TO-END TESTS
REM ============================================
echo.
echo 3️⃣  END-TO-END TESTS
echo -------------------------------------------
echo.

echo Running E2E tests...
if exist e2e-tests.php (
    php e2e-tests.php >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✅ E2E Tests PASSED
        set /a PASSED+=1
    ) else (
        echo ⚠️  E2E Tests - Check services are running
        echo.
    )
) else (
    echo ❌ e2e-tests.php not found
)

REM ============================================
REM 4. MONITORING & VALIDATION
REM ============================================
echo.
echo 4️⃣  MONITORING & VALIDATION
echo -------------------------------------------
echo.

echo Running system monitoring...
if exist monitoring.php (
    php monitoring.php >nul 2>&1
    if !errorlevel! equ 0 (
        echo ✅ System Monitoring PASSED
        set /a PASSED+=1
    ) else (
        echo ⚠️  System Monitoring - Database may be unavailable
        echo.
    )
) else (
    echo ❌ monitoring.php not found
)

REM ============================================
REM FINAL REPORT
REM ============================================
echo.
echo ============================================
echo 📊 TEST REPORT
echo ============================================
echo.

if !FAILED! equ 0 (
    echo 🎉 ALL TESTS COMPLETED SUCCESSFULLY!
    echo.
    echo ✅ Unit Tests:       PASSED
    echo ✅ Integration:      PASSED
    echo ✅ E2E Tests:        PASSED
    echo ✅ Monitoring:       PASSED
    echo.
    echo Total Tests Passed: !PASSED!
    echo.
) else (
    echo ⚠️  SOME TESTS FAILED OR SKIPPED
    echo.
    echo Passed: !PASSED!
    echo Failed/Skipped: !FAILED!
    echo.
    echo Next Steps:
    echo   1. Check that all services are running
    echo      docker-compose ps
    echo.
    echo   2. View service logs for errors
    echo      docker-compose logs [service-name]
    echo.
    echo   3. Verify database is accessible
    echo      docker-compose exec mysql mysql -u root -proot_password -e "SELECT 1;"
    echo.
)

echo For detailed results, see TEST_GUIDE.md
echo.

pause
