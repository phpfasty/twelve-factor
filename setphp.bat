@echo off
setlocal

set "PROJECT_DIR=%~dp0"
set "PHP_BIN=%PROJECT_DIR%.php\php\php.exe"

if not exist "%PHP_BIN%" (
    echo [setphp] PHP binary not found: %PHP_BIN%
    echo [setphp] Install or restore PHP in .php\php first.
    exit /b 1
)

set "PATH=%PROJECT_DIR%.php\php;%PATH%"
set "PHP_INI_SCAN_DIR=%PROJECT_DIR%.php\php\;"

set "COMPOSER=%PROJECT_DIR%.php\composer.phar"

echo [setphp] Local PHP active: %PHP_BIN%
echo [setphp] Add .php\php to PATH for this shell session.
echo [setphp] Run: php -v

goto :EOF
