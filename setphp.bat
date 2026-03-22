@echo off
setlocal

set "PROJECT_DIR=%~dp0"
set "PHP_DIR=%PROJECT_DIR%.php\php"
set "PHP_BIN=%PHP_DIR%\php.exe"

if not exist "%PHP_BIN%" (
    echo [setphp] No bundled PHP at .php\php — use `php` from your PATH (install PHP 8.4+).
    exit /b 0
)

set "PATH=%PHP_DIR%;%PATH%"
set "PHP_INI_SCAN_DIR=%PHP_DIR%\"

echo [setphp] Prepended bundled PHP to PATH for this session. Run: php -v

goto :EOF
