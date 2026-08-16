#!/usr/bin/env bash
# Start Worldly on http://localhost:8000 (override with PORT=xxxx ./serve.sh)
set -euo pipefail
PORT="${PORT:-8000}"

# Absolute URLs in the sitemap, robots.txt and canonical tags follow this.
# Set it to your real origin in production.
export WORLDLY_BASE_URL="${WORLDLY_BASE_URL:-http://localhost:${PORT}}"

# Keep local traffic out of the production Analytics property. Unset this, or
# set a measurement ID, if you need to test the tag itself.
export WORLDLY_GA_ID="${WORLDLY_GA_ID-}"
cd "$(dirname "$0")"
echo "🌍 Worldly running at http://localhost:${PORT}"
exec php -S "localhost:${PORT}" -t public public/index.php
