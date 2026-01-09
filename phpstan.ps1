#!/usr/bin/env pwsh
# PHPStan wrapper script for Windows
# Sets proper temp directories before running PHPStan

$env:TEMP="$env:USERPROFILE\AppData\Local\Temp"
$env:TMP="$env:USERPROFILE\AppData\Local\Temp"

& "C:\Users\iraul\scoop\apps\php\current\php.exe" vendor/bin/phpstan $args
