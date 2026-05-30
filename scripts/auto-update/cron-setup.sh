#!/usr/bin/env bash
# Install cron job for bisped.net auto-update ingestion
# Run once as the web user: bash scripts/auto-update/cron-setup.sh

BISPED_DIR="$(cd "$(dirname "$0")/../.." && pwd)"
FRANKENPHP="${BISPED_DIR}/runtime/bin/frankenphp"
INGEST="${BISPED_DIR}/scripts/auto-update/ingest.php"
LOGDIR="${BISPED_DIR}/storage"
LOCKFILE="${BISPED_DIR}/storage/ingest.lock"

# Check frankenphp exists
if [ ! -f "$FRANKENPHP" ]; then
    echo "ERROR: frankenphp not found at $FRANKENPHP"
    exit 1
fi

mkdir -p "$LOGDIR"

CRON_LINE="0 6 * * * flock -n ${LOCKFILE} ${FRANKENPHP} php-cli ${INGEST} --all --limit=10 >> ${LOGDIR}/ingest-cron.log 2>&1"

# Add to crontab if not already present
(crontab -l 2>/dev/null | grep -F "$INGEST") && {
    echo "Cron job already installed."
    exit 0
}

(crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -
echo "Cron job installed: runs daily at 06:00"
echo "Log: ${LOGDIR}/ingest-cron.log"
