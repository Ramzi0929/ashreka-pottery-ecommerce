@echo off
echo Installing Document Reader Packages...
echo.

cd /d "c:\xampp\htdocs\ashreka-pottery-system advanced\document_reader"

echo Current directory: %CD%
echo.

echo Installing composer packages...
composer install

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ Document reader packages installed successfully!
    echo.
    echo You can now use PDF and Word document reading functionality.
) else (
    echo.
    echo ❌ Installation failed. Make sure composer is installed.
    echo.
    echo To install composer, visit: https://getcomposer.org/download/
)

echo.
pause