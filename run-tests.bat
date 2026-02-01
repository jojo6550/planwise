@echo off
REM PlanWise Test Runner Script
REM Runs PHPUnit tests for CS334 project

echo ============================================
echo PlanWise - PHPUnit Test Runner
echo ============================================
echo.

REM Check if vendor directory exists
if not exist "vendor\" (
    echo ERROR: Composer dependencies not installed
    echo.
    echo Please run: composer install
    echo.
    pause
    exit /b 1
)

REM Check if PHPUnit exists
if not exist "vendor\bin\phpunit" (
    echo ERROR: PHPUnit not found
    echo.
    echo Please run: composer install
    echo.
    pause
    exit /b 1
)

echo Running all PHPUnit tests...
echo.

REM Run PHPUnit
vendor\bin\phpunit --colors=always --testdox

echo.
echo ============================================
echo Test run complete!
echo ============================================
echo.

REM Check if tests passed (errorlevel 0 means success)
if %ERRORLEVEL% EQU 0 (
    echo Result: ALL TESTS PASSED! ✓
) else (
    echo Result: SOME TESTS FAILED! ✗
    echo.
    echo Review the output above for details.
)

echo.
echo To run specific tests:
echo   vendor\bin\phpunit tests\Unit\AuthTest.php
echo   vendor\bin\phpunit tests\Unit\ValidatorTest.php
echo.
echo To generate coverage report:
echo   vendor\bin\phpunit --coverage-html coverage
echo.
pause
