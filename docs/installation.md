# Installation

## Requirements

- PHP 8.4+ (PHP 8.5 preferred)
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

### Windows

On Windows, install a matching `php-ast` DLL for the installed PHP version and enable it in `php.ini`:

```ini
extension=php_ast.dll
```

The provided Windows installer handles this automatically.

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

(Optional) verify PHP version:

```bash
php -r "echo PHP_MAJOR_VERSION,'.',PHP_MINOR_VERSION,PHP_EOL;"
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

## Installer Scripts

- Windows 11: `install/windows.cmd`
- macOS: `install/macos.sh`
- Ubuntu: `install/ubuntu.sh`

---

## Notes

- Simple C++ runs directly from the repository
- No global install step is required
- The PHP generator requires `php-ast`
- Installer scripts create the `s++` launcher in the user bin directory
- Windows installs and enables `php-ast` automatically
