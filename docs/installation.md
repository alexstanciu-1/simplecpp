# Installation

## Requirements

- PHP 8.5
- PHP extension: `php-ast`
- C++23-compatible compiler:
  - GCC 13+
  - Clang 16+
  - MSVC (latest)

Simple C++ targets modern toolchains. Older compilers are not supported.

---

## PHP Setup

The PHP generator requires the `php-ast` extension.

### Linux / Ubuntu

Install the extension:

```bash
sudo apt update
sudo apt install -y php-ast
```

### PECL

Or install it via PECL:

```bash
pecl install ast
```

Then enable it in `php.ini` if needed:

```ini
extension=ast
```

### Verify PHP setup

```bash
php -m | grep ast
```

Expected output:

```text
ast
```

---

## Quick Start (Linux / Ubuntu)

Install required tools:

```bash
sudo apt update
sudo apt install -y php php-ast g++-13
```

(Optional) verify compiler:

```bash
g++-13 --version
```

---

## Running from Repository

No global installation is required.

Typical flow:

```bash
php php_generator/bin/run.php input.php
g++-13 -std=c++23 output.cpp -o output
./output
```

---

## CI Environments (GitHub Actions)

CI installs a compatible compiler explicitly.

Example:

```yaml
- name: Install GCC 13
  run: |
    sudo apt update
    sudo apt install -y g++-13

- name: Use GCC 13
  run: |
    echo "CC=gcc-13" >> $GITHUB_ENV
    echo "CXX=g++-13" >> $GITHUB_ENV
```

---

## Notes

- Simple C++ runs directly from the repository
- No global install step is required
- The PHP generator requires `php-ast`
- Future installer scripts may simplify setup

---

## Installer Scripts

- Windows 11: `install/windows.cmd`
- macOS: `install/macos.sh`
- Ubuntu: `install/ubuntu.sh`
