#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")" && pwd)"
TARGET="${1:-/usr/local/bin/berkat-deploy}"

chmod +x "$PROJECT_ROOT/deploy.sh"
ln -sf "$PROJECT_ROOT/deploy.sh" "$TARGET"

echo "Installed deploy command: $TARGET"
echo "You can now run: $(basename "$TARGET")"