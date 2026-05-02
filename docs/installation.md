# Installation
Doc Status: supporting
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
- Ninja
- C++23-compatible compiler:
	- GCC 13+
	- Clang 16+
	- MSVC-compatible environment or another compiler explicitly configured in `prism.json`

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

- installs Microsoft Visual C++ Redistributable, Ninja, and PHP with `winget`
- installs Git with `winget` only when `git` is not already on PATH
- checks PHP version
- runs `install/install.php`
- verifies `php-ast`
- installs `scpp.cmd`, `scpp` (for Git Bash / MinGW), `scpp.php`, and `scpp.json`
- ensures the user PATH contains the user-local install directory
- on Windows, if PHP cURL cannot validate the TLS chain for the php-ast download, the installer retries through PowerShell using the Windows certificate store
- on Windows, the final php-ast verification now uses `extension_loaded('ast')` in a fresh PHP process rather than parsing `php -m`, so unrelated startup warnings from other extensions are less likely to cause a false failure
- Windows PATH updates are applied through a temporary PowerShell script and fall back to `setx` if the first attempt fails

### Ubuntu / Debian

Run:

```bash
./install/ubuntu.sh
```

What it does:

- installs prerequisites with `apt`, including `ninja-build`
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

- installs prerequisites with Homebrew, including `ninja`
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
- `--doctor` prints PHP binary, PHP version, ini path, `php-ast` status, repo root, CLI entrypoint, detected project config, Ninja path, and default compiler

---

## Basic usage

Initialize a project in the current directory:

```bash
scpp init
```

Build the configured entrypoint from `prism.json`:

```bash
scpp build
```

Build then run the configured entrypoint:

```bash
scpp run
```

Pass program arguments after `--` when needed:

```bash
scpp run -- arg1 arg2
```

Run the deterministic usability harness:

```bash
scpp usability-harness
scpp usability-harness --all
scpp usability-harness --kind scenario
scpp usability-harness --campaign scenarios_multifile
scpp usability-harness --template scenario_bool_null_gate_001
```

Transpile one PHP file to generated C++ printed on stdout:

```bash
scpp input.phs
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

## Windows troubleshooting

- a startup warning for another extension, such as a missing `php_yaml_*.dll`, is a separate local PHP environment issue; it should be fixed, but it is not treated as a php-ast installation failure if the fresh-process `extension_loaded('ast')` check succeeds


- Windows installer resolves relative `extension_dir` values such as `ext` against the active PHP installation directory before copying `php_ast.dll`.

## Ninja install notes

Minimal install commands:

- Windows: `winget install Ninja-build.Ninja`
- Ubuntu/Debian: `sudo apt update && sudo apt install ninja-build`
- macOS: `brew install ninja`

`scpp build` treats missing Ninja as a hard error and prints a platform-specific install hint.
