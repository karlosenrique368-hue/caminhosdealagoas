#!/bin/sh
set -e

# On first boot after a new deploy, seed the persistent volume with any files
# that were baked into the Docker image at build time.
# cp -n = no-clobber (never overwrite files already in the volume).
if [ -d /app/storage/uploads.baked ]; then
  cp -rn /app/storage/uploads.baked/. /app/storage/uploads/ 2>/dev/null || true
fi

exec php -S 0.0.0.0:${PORT:-8080} -t public public/index.php
