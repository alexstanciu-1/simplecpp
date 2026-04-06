#!/usr/bin/env bash
set -euo pipefail

echo "Simple C++ installer for Ubuntu"
echo

if ! command -v apt >/dev/null 2>&1; then
	echo "ERROR: This installer targets Ubuntu."
	exit 1
fi

echo "Installing dependencies..."
sudo apt update
sudo apt install -y software-properties-common curl git php php-dev php-pear php-ast g++-13

echo
echo "Verifying php-ast..."
php -m | grep -i '^ast$' >/dev/null

echo
echo "Creating user bin directory..."
mkdir -p "$HOME/.d-app"

echo
echo "Running project installer..."
cd "$(dirname "$0")/.."
php "install.php"

echo
echo "Installation finished."
