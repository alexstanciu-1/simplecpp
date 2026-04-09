#!/usr/bin/env bash
set -euo pipefail

echo "Prism++ installer for Ubuntu/Debian"
echo

if [ "${EUID}" -eq 0 ]; then
	echo "ERROR: Do not run this script with sudo or as root."
	echo "Run it as your normal user. The script will use sudo only for apt commands."
	exit 1
fi

if ! command -v apt >/dev/null 2>&1; then
	echo "ERROR: This installer targets Ubuntu/Debian."
	exit 1
fi

REPO_ROOT="$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)"

choose_php_bin() {
	if command -v php8.5 >/dev/null 2>&1; then
		echo "php8.5"
		return 0
	fi
	if command -v php8.4 >/dev/null 2>&1; then
		echo "php8.4"
		return 0
	fi
	if command -v php >/dev/null 2>&1; then
		echo "php"
		return 0
	fi
	return 1
}

PHP_BIN="$(choose_php_bin || true)"
if [ -z "$PHP_BIN" ]; then
	echo "Installing dependencies..."
	sudo apt update
	sudo apt install -y software-properties-common curl git php php-dev php-pear php-ast g++ ninja-build lld
	PHP_BIN="$(choose_php_bin || true)"
else
	echo "Installing dependencies..."
	sudo apt update
	sudo apt install -y software-properties-common curl git php php-dev php-pear php-ast g++ ninja-build lld
fi

if [ -z "$PHP_BIN" ]; then
	echo "ERROR: No supported PHP binary found after dependency installation."
	exit 1
fi

echo "Using PHP binary: $PHP_BIN"

echo
echo "Ensuring sccache..."
if apt-cache show sccache >/dev/null 2>&1; then
	sudo apt install -y sccache
else
	echo "WARNING: sccache is not available from the configured apt sources. Continuing without it."
fi

echo
echo "Verifying PHP 8.4+..."
"$PHP_BIN" -r 'exit(version_compare(PHP_VERSION, "8.4.0", ">=") ? 0 : 1);'

echo
echo "Verifying php-ast..."
"$PHP_BIN" -m | grep -i '^ast$' >/dev/null

echo
echo "Running repo-based user-local installer..."
cd "$REPO_ROOT"
SCPP_INSTALL_PHP_BIN="$PHP_BIN" "$PHP_BIN" "install/install.php"

echo
echo "Verifying launcher on PATH..."
if ! command -v scpp >/dev/null 2>&1; then
	echo "WARNING: scpp is installed but not visible in the current shell yet."
	echo "Run: source ~/.profile"
else
	echo "Launcher available: $(command -v scpp)"
fi

echo
echo "Installation finished."
echo "Open a new shell or run: source ~/.profile"
