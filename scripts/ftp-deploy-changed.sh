#!/usr/bin/env bash
# Deploy mirato via FTPS dei file cambiati, senza GitHub Actions.
# Legge le credenziali da .deploy-ftp.local (gitignored).
# Uso: bash scripts/ftp-deploy-changed.sh
set -euo pipefail

cd "$(dirname "$0")/.."
CREDS=".deploy-ftp.local"
[ -f "$CREDS" ] || { echo "ERRORE: manca $CREDS"; exit 1; }
# shellcheck disable=SC1090
source "$CREDS"
: "${FTP_HOST:?manca FTP_HOST}"; : "${FTP_USER:?manca FTP_USER}"
: "${FTP_PASS:?manca FTP_PASS}"; : "${FTP_REMOTE_DIR:?manca FTP_REMOTE_DIR}"
REMOTE="${FTP_REMOTE_DIR%/}"

# File da pubblicare (relativi alla root del repo)
FILES=(
  "app/Support/HtmlSanitizer.php"
  "app/Support/I18n.php"
  "app/Views/layouts/main.php"
  "app/Views/public/servizi-content.php"
  "app/Views/public/contact-content.php"
  "app/Views/public/sostenibilita-content.php"
  "app/Views/public/legal-content.php"
  "public/assets/css/app.css"
  "public/media/pages/sostenibilita-tecnico-microscopio.jpg"
)

ok=0; fail=0
for f in "${FILES[@]}"; do
  [ -f "$f" ] || { echo "SKIP (manca in locale): $f"; continue; }
  url="ftp://${FTP_HOST}/${REMOTE}/${f}"
  if curl -sS --ftp-ssl --ftp-create-dirs -T "$f" "$url" --user "${FTP_USER}:${FTP_PASS}"; then
    echo "OK   $f"
    ok=$((ok+1))
  else
    echo "FAIL $f"
    fail=$((fail+1))
  fi
done
echo "---"
echo "Caricati: $ok  Falliti: $fail"
