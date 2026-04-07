# Installation

## Current install model

The current installer is:

- repo-based
- current-user only
- user-local binary install

The installer does **not** copy the repository elsewhere.
It installs a user-local `scpp` launcher that points back to this checkout.

That means:

- if you move or delete the repository, rerun the installer
- the installed launcher is only for the current user
- multi-user or system-wide install is not part of this milestone

---

## Requirements

- PHP 8.4+ (`PHP 8.5` preferred)
- PHP extension: `php-ast`
- C++23-compatible compiler:
	- GCC 13+
	- Clang 16+
	- MSVC (latest)

Prism++ targets modern toolchains. Older compilers are not supported.

---

## User-local install locations

### Windows

The installer writes the launcher into:

```text
%LOCALAPPDATA%\Programs\scpp\bin
```

### Linux and macOS

The installer writes the launcher into:

```text
~/.local/bin
```

On Linux and macOS, the installer also appends a guarded PATH block to:

- `~/.profile`
- `~/.bash_profile`
- `~/.zprofile`

This makes `~/.local/bin` available in normal interactive shells.

---

## Platform installers

### Windows

Run:

```bat
install\windows.cmd
```

What it does:

- installs common prerequisites with `winget`
- checks PHP version
- runs `install/install.php`
- verifies `php-ast`
- installs `scpp.cmd`, `scpp.php`, and `scpp.json`
- ensures the user PATH contains the user-local install directory

### Ubuntu / Debian

Run:

```bash
./install/ubuntu.sh
```

What it does:

- installs prerequisites with `apt`
- verifies `php-ast`
- runs `install/install.php`
- installs `scpp`, `scpp.php`, and `scpp.json`
- automatically appends `~/.local/bin` to shell profile files

### macOS

Run:

```bash
./install/macos.sh
```

What it does:

- installs prerequisites with Homebrew
- installs/enables `php-ast`
- runs `install/install.php`
- installs `scpp`, `scpp.php`, and `scpp.json`
- automatically appends `~/.local/bin` to shell profile files

---

## Manual installer entrypoint

The platform scripts call:

```bash
php install/install.php
```

This script is the authoritative repo-based installer.

---

## Verify installation

After installation, open a new terminal and run:

```bash
scpp --version
scpp --doctor
```

Expected:

- `--version` prints the current CLI version
- `--doctor` prints PHP binary, PHP version, ini path, `php-ast` status, repo root, and CLI entrypoint

---

## Basic usage

Transpile one PHP file to generated C++ printed on stdout:

```bash
scpp input.php
```

Show help:

```bash
scpp --help
```

---

## Uninstall

Cross-platform uninstall entrypoint:

```bash
php install/uninstall.php
```

Convenience wrappers are also provided:

- `install/uninstall.sh`
- `install/uninstall.cmd`

Uninstall removes the user-local launcher files.
The repository checkout itself is not removed.

---

## Notes

- This milestone is only about getting a stable user-local binary in place.
- Multi-file compilation semantics are **not** part of the installer itself.
- The current launcher name is `scpp`.
- The old `d-app` and `s++` names are obsolete.
