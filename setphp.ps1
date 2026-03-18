$ErrorActionPreference = 'Stop'

$ProjectDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$PhpDir = Join-Path $ProjectDir '.php\php'
$PhpBin = Join-Path $PhpDir 'php.exe'

if (-not (Test-Path -LiteralPath $PhpBin)) {
    Write-Error "[setphp] PHP binary not found: $PhpBin`n[setphp] Install or restore PHP in .php\php first."
    throw
}

$env:Path = "$PhpDir;" + $env:Path
$env:PHP_INI_SCAN_DIR = "$PhpDir\"

Write-Host "[setphp] Local PHP active: $PhpBin"
Write-Host "[setphp] Added .php\php to PATH for current PowerShell session"
Write-Host "[setphp] Run: php -v"
