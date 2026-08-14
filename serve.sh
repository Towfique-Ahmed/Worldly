#!/usr/bin/env bash
# Start Worldly on http://localhost:8000 (override with PORT=xxxx ./serve.sh)
set -euo pipefail
PORT="${PORT:-8000}"
cd "$(dirname "$0")"
echo "🌍 Worldly running at http://localhost:${PORT}"
exec php -S "localhost:${PORT}" -t public public/index.php
