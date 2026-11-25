#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."
php "install/uninstall.php"
