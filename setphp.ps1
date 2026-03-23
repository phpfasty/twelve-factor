$ErrorActionPreference = 'Stop'

$ProjectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$PhpDir = Join-Path $ProjectDir '.php\php'
$PhpBin = Join-Path $PhpDir 'php.exe'

if (-not (Test-Path -LiteralPath $PhpBin)) {
    Write-Host '[setphp] No bundled PHP at .php\php — use `php` from your PATH (install PHP 8.4+).'
    exit 0
}

$env:Path = "$PhpDir;" + $env:Path
$env:PHP_INI_SCAN_DIR = "$PhpDir\"

Write-Host '[setphp] Prepended bundled PHP to PATH for this PowerShell session. Run: php -v'
