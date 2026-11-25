# Prism++ CLI Installation and Project Bootstrap Milestone
Doc Status: planning
Status: Active for the current first-binary and first-project-bootstrap milestone.

---

## 1. Goal

This milestone defines what it means for Prism++ to have:

- a usable installed `scpp` binary
- a stable project bootstrap command
- a first practical build command shape

The goal is still narrow:

> A current user can install `scpp`, create `prism.json`, and run `scpp build` from a normal shell without manually wiring Ninja invocation.

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
- `scpp` on Windows too, so Git Bash / MinGW can run `scpp` directly
- `scpp.php`
- `scpp.json`

### Linux / macOS

- `scpp`
- `scpp.php`
- `scpp.json`

`scpp.php` is the installed shim.
It reads sibling launcher config and forwards execution to the repo CLI entrypoint.

`scpp.json` must contain at least:

- `repo_root`

This launcher config is separate from the project config `prism.json`.

---

## 6. Public CLI contract for this milestone

Required commands:

- `scpp <input.phs>`
- `scpp init`
- `scpp build`
- `scpp clean`
- `scpp update`
- `scpp --help`
- `scpp --version`
- `scpp --doctor`

`scpp init` must:

- create `prism.json` in the current directory
- create `.prism/build`, `.prism/generated`, and `.prism/cache`
- guess a common entrypoint when possible
- otherwise write a placeholder entrypoint the user edits

`scpp build` must:

- discover `prism.json` by walking upward from the current directory
- use one entrypoint first
- keep generated C++ on disk
- emit a Ninja build file under `.prism/build/`
- invoke Ninja directly
- produce one executable first
- use project-root-relative normalized forward-slash paths in the emitted Ninja file
- compile the runtime directly from the repo checkout

`scpp clean` must:

- discover `prism.json` by walking upward from the current directory
- remove the `.prism/` working tree when configured build, generated, and cache directories all live inside it
- otherwise remove configured build, generated, and cache directories
- include resolved Prism project dependencies
- leave source files and `prism.json` in place
- refuse unsafe clean targets outside the owning project root

`scpp update` must:

- update the installed repo checkout from `origin/main`
- fetch release tags while updating
- require branch `main`
- require a clean working tree
- use fast-forward-only Git operations
- fail clearly instead of overwriting local changes or creating a merge commit

`--doctor` must report enough information to debug install and build failures quickly, including:

- PHP binary
- PHP version
- CLI version resolved from the latest reachable `v*` release tag when the installed repo root is a Git checkout, falling back to the built-in dev version otherwise
- php.ini path
- whether `php-ast` is loaded
- repo root
- CLI entrypoint path
- detected project config path when present
- Git checkout branch and commit when the installed repo root is a Git checkout
- best-effort `origin/main` commit and up-to-date status when the remote can be queried non-interactively
- Ninja path when present
- detected default compiler when present

---

## 7. Project config contract

The project config filename for this milestone is:

- `prism.json`

Minimum shape:

```json
{
  "config_version": 1,
  "project_name": "my_project",
  "entrypoint": "main.phs",
  "build_dir": ".prism/build",
  "generated_dir": ".prism/generated",
  "cache_dir": ".prism/cache",
  "build": {
    "backend": "ninja",
    "cxx": null
  }
}
```

The default project shape is not web-first. `scpp init` should prefer a conventional CLI-style entrypoint such as `main.phs` before any index-based candidate.

Compiler policy for this milestone:

- detect a sane default compiler
- allow override in config
- fail clearly if none is found

---

## 8. Installer requirements

The installer must be:

- idempotent
- current-user only
- safe to rerun after repo updates

It must also:

- verify minimum PHP version
- verify or install `php-ast` according to platform capabilities
- provision Ninja on supported platform installers
- on Windows, do not attempt a Git install or upgrade when `git` is already available on PATH
- ensure the launcher directory is reachable via PATH or shell profile
- perform post-install verification with `scpp --version`
- warn clearly that repo moves require reinstall

This milestone does not require the installer itself to bootstrap Ninja. Missing Ninja remains a build-time hard error with a platform-specific install hint.

---

## 9. Uninstall requirements

A minimal uninstall path must exist.

For this milestone, uninstall removes:

- launcher files
- profile block / user PATH entry added by the installer

It does not remove the repository checkout.

---

## 10. Non-goals

This milestone does not yet define:

- full include resolution semantics
- the final `__DIR__` static-expression subset
- cross-file symbol merge rules
- `_once` identity rules
- final duplicate-definition policy across source files
- the full project graph algorithm

Those belong to the deliberate multi-file model milestone.
