#!/usr/bin/env bash
set -euo pipefail

echo "Simple C++ installer for macOS"
echo

if ! command -v brew >/dev/null 2>&1; then
	echo "ERROR: Homebrew is required."
	echo "Install it from https://brew.sh and run this script again."
	exit 1
fi

echo "Installing dependencies..."
brew update
brew install git php gcc

echo
echo "Ensuring php-ast is installed..."
if ! php -m | grep -qi '^ast$'; then
	pecl install ast
fi

PHP_INI="$(php --ini | awk -F': ' '/Loaded Configuration File/ {print $2}')"
if [ -z "${PHP_INI:-}" ] || [ "${PHP_INI}" = "(none)" ]; then
	PHP_INI="$(php --ini | awk -F': ' '/Scan for additional \.ini files in/ {print $2}')/99-simple-cpp.ini"
	echo "extension=ast" | tee "$PHP_INI" >/dev/null
elif ! php -m | grep -qi '^ast$'; then
	if ! grep -Eq '^extension *= *ast$' "$PHP_INI"; then
		printf "\nextension=ast\n" >> "$PHP_INI"
	fi
fi

echo
echo "Creating user bin directory..."
mkdir -p "$HOME/.d-app"

echo
echo "Running project installer..."
cd "$(dirname "$0")/.."
php "install.php"

echo
echo "Installation finished."
echo "If needed, restart your terminal session."
