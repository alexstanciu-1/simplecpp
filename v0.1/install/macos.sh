#!/usr/bin/env bash
set -euo pipefail

echo "Prism++ installer for macOS"
echo

if ! command -v brew >/dev/null 2>&1; then
	echo "ERROR: Homebrew is required."
	echo "Install it from https://brew.sh and run this script again."
	exit 1
fi

echo "Installing dependencies..."
brew update
brew install git php gcc ninja sccache lld

echo
echo "Verifying PHP 8.4+..."
php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);'

echo
echo "Ensuring php-ast is installed..."
if ! php -m | grep -qi '^ast$'; then
	printf "\n" | pecl install ast || true
fi

if ! php -m | grep -qi '^ast$'; then
	PHP_INI="$(php --ini | awk -F': ' '/Loaded Configuration File/ {print $2}')"
	if [ -z "${PHP_INI:-}" ] || [ "${PHP_INI}" = "(none)" ]; then
		SCAN_DIR="$(php --ini | awk -F': ' '/Scan for additional \.ini files in/ {print $2}')"
		mkdir -p "$SCAN_DIR"
		PHP_INI="$SCAN_DIR/99-prismpp.ini"
	fi

	if ! grep -Eq '^extension *= *ast$' "$PHP_INI" 2>/dev/null; then
		printf "\nextension=ast\n" >> "$PHP_INI"
	fi
fi

php -m | grep -qi '^ast$'

echo
echo "Running repo-based user-local installer..."
cd "$(dirname "$0")/.."
php "install/install.php"

echo
echo "Installation finished."
echo "Open a new shell or run: source ~/.profile"
