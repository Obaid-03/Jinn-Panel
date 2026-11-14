#!/usr/bin/env bash
# Builds the prototype stylesheet (dev/prototype/public/proto.css). Same Tailwind as the app.
set -euo pipefail
cd "$(dirname "$0")"
if command -v tailwindcss >/dev/null 2>&1; then tw=(tailwindcss); else tw=(npx --yes tailwindcss@3.4.17); fi
"${tw[@]}" -c tailwind.config.js -i input.css -o public/proto.css --minify "$@"
