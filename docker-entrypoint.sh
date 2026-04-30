#!/bin/sh
set -e

# Ensure uploads directory exists and is writable in runtime.
mkdir -p /app/storage/uploads
chmod -R ug+rwX /app/storage/uploads 2>/dev/null || true

# On first boot after a new deploy, seed the persistent volume with any files
# that were baked into the Docker image at build time.
# cp -n = no-clobber (never overwrite files already in the volume).
if [ -d /app/storage/uploads.baked ]; then
  cp -rn /app/storage/uploads.baked/. /app/storage/uploads/ 2>/dev/null || true
fi

# Health marker helps confirm persistence across deploys.
if [ ! -f /app/storage/uploads/.runtime-write-check ]; then
  date -u +"%Y-%m-%dT%H:%M:%SZ" > /app/storage/uploads/.runtime-write-check 2>/dev/null || true
fi

exec php -c /app/php.ini -S 0.0.0.0:${PORT:-8080} -t public public/index.php
