#!/usr/bin/env bash
set -euo pipefail

echo "Prism++ installer for Ubuntu/Debian"
echo

if ! command -v apt >/dev/null 2>&1; then
	echo "ERROR: This installer targets Ubuntu/Debian."
	exit 1
fi

echo "Installing dependencies..."
sudo apt update
sudo apt install -y software-properties-common curl git php php-dev php-pear php-ast g++ ninja-build lld

echo
echo "Ensuring sccache..."
if apt-cache show sccache >/dev/null 2>&1; then
	sudo apt install -y sccache
else
	echo "WARNING: sccache is not available from the configured apt sources. Continuing without it."
fi

echo
echo "Verifying PHP 8.4+..."
php -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);'

echo
echo "Verifying php-ast..."
php -m | grep -i '^ast$' >/dev/null

echo
echo "Running repo-based user-local installer..."
cd "$(dirname "$0")/.."
php "install/install.php"

echo
echo "Installation finished."
echo "Open a new shell or run: source ~/.profile"
