$ErrorActionPreference = 'Stop'
Push-Location -LiteralPath $PSScriptRoot
try {
    node scripts/dev/manage.mjs stop
} finally {
    Pop-Location
}
