# Prism++ CLI Installation Milestone

Status: Active for the current first-binary milestone.

---

## 1. Goal

This milestone defines what it means for Prism++ to have a usable binary before the deliberate multi-file model is implemented.

The goal is narrow:

> A current user can install a stable `scpp` command and invoke it from a normal shell without having to cd into the repository.

This milestone is intentionally separate from:

- `require` / `include` semantics
- cross-file symbol visibility
- duplicate-definition policy
- deliberate multi-file compilation context

---

## 2. Install model

The install model for this milestone is:

- repo-based
- current-user only
- user-local binary install

Meaning:

- the installer does not relocate or copy the repository
- the installed launcher points back to the repository checkout
- moving or deleting the repository invalidates the installed launcher
- reinstall is required after a repo move

---

## 3. Public command name

The public command name for this milestone is:

- `scpp`

The following older names are obsolete and must not be used in new docs or installer output:

- `d-app`
- `s++`

---

## 4. User-local install locations

### Windows

Normal user-local install location:

```text
%LOCALAPPDATA%\Programs\scpp\bin
```

### Linux / macOS

Normal user-local install location:

```text
~/.local/bin
```

On Linux and macOS, the installer must automatically append a guarded PATH block so `~/.local/bin` is available in normal shells.

---

## 5. Installed launcher set

The installer writes a small launcher set into the user-local bin directory:

### Windows

- `scpp.cmd`
- `scpp.php`
- `scpp.json`

### Linux / macOS

- `scpp`
- `scpp.php`
- `scpp.json`

`scpp.php` is the installed shim.
It reads sibling config and forwards execution to the repo CLI entrypoint.

`scpp.json` must contain at least:

- `repo_root`

---

## 6. Public CLI contract for this milestone

Required commands:

- `scpp <input.php>`
- `scpp --help`
- `scpp --version`
- `scpp --doctor`

`--doctor` must report enough information to debug install failures quickly, including:

- PHP binary
- PHP version
- php.ini path
- whether `php-ast` is loaded
- repo root
- CLI entrypoint path

---

## 7. Installer requirements

The installer must be:

- idempotent
- current-user only
- safe to rerun after repo updates

It must also:

- verify minimum PHP version
- verify or install `php-ast` according to platform capabilities
- ensure the launcher directory is reachable via PATH or shell profile
- perform post-install verification with `scpp --version`
- warn clearly that repo moves require reinstall

---

## 8. Uninstall requirements

A minimal uninstall path must exist.

For this milestone, uninstall removes:

- launcher files
- profile block / user PATH entry added by the installer

It does not remove the repository checkout.

---

## 9. Non-goals

This milestone does not define:

- include resolution semantics
- `__DIR__` execution semantics across multiple source units
- symbol merge rules across files
- `_once` identity rules
- duplicate-definition policy across source files

Those belong to the deliberate multi-file model milestone.
