$ErrorActionPreference = 'Stop'
Push-Location -LiteralPath $PSScriptRoot
try {
    node scripts/dev/manage.mjs start
    if ($LASTEXITCODE -ne 0) { throw 'Avvio non riuscito: consultare .local/logs' }
} finally {
    Pop-Location
}
