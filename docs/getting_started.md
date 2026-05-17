# Getting Started
Doc Status: supporting
## 1. Install the CLI

Use the platform installer:

- Windows: `install\windows.cmd`
- Ubuntu/Debian: `./install/ubuntu.sh`
- macOS: `./install/macos.sh`

Then open a new terminal and verify:

```bash
scpp --version
scpp --doctor
```

Update the installed `scpp` checkout later:

```bash
scpp update
```

```bash
scpp update --force
```

`scpp update` fetches GitHub `origin/main` and fast-forwards the checkout. It requires the checkout to be on `main` with no local changes. When a real update is applied, it also rebuilds the default reusable runtime cache. `scpp update --force` rebuilds that default runtime cache even when the checkout is already current.

If an existing project shows odd build or generation behavior right after `scpp update`, and the same problem does not reproduce in a fresh project, clear that project's `.prism/` state once and rebuild:

```bash
scpp clean
scpp build
```

This is a troubleshooting step for stale project state after an update, not the normal day-to-day workflow.

## 2. Start a project

From the project root:

```bash
scpp init
```

Or choose the PHP profile explicitly:

```bash
scpp init --php-profile=legacy
scpp init --php-profile=strict
```

This creates:

- `prism.json`
- `.prism/build/`
- `.prism/generated/`
- `.prism/cache/`

The current default PHP runtime profile is `legacy`.

For profile-specific example projects and agent-facing usage guidance, see:

- `docs/examples/php/README.md`
- `docs/examples/php/strict/README.md`
- `docs/examples/php/legacy/README.md`

When starting a new strict-profile project, prefer the strict examples first and avoid mixing strict and legacy library surfaces in the same project.

`scpp init` first looks for a common non-web-first PHP++ entrypoint such as:

- `main.phs`
- `src/main.phs`
- `app/main.phs`
- `index.phs`
- `src/index.phs`
- `main.php`
- `src/main.php`
- `app/main.php`
- `index.php`
- `src/index.php`

If none is found, it writes a placeholder entrypoint and you must edit `prism.json` before building.

## 3. Build or run the configured entrypoint

Build only:

```bash
scpp build
scpp build --entry=tests/php/sample.phs
```

By default, `scpp build` reuses existing runtime and dependency artifacts. Build those layers explicitly when needed:

```bash
scpp build --build-runtime
scpp build --build-dependencies
scpp build --build-runtime --build-dependencies
scpp build --force
```

Remove generated state for a full cold rebuild:

```bash
scpp clean
```

`scpp clean` removes configured build, generated, and cache directories for the root project and resolved project dependencies.
With the standard layout, that means removing the whole `.prism/` working tree.

Build then run:

```bash
scpp run
scpp run --entry=tests/php/sample.phs
```

The same explicit rebuild flags are supported on `scpp run`:

```bash
scpp run --build-runtime
scpp run --build-dependencies -- arg1 arg2
scpp run --force
```

Pass program arguments after `--`:

```bash
scpp run -- arg1 arg2
```

Rebuild the reusable runtime cache directly:

```bash
scpp runtime-build
scpp runtime-build --release
scpp runtime-build --force
```

Print local documentation without web access:

```bash
scpp docs
scpp docs strict
scpp docs diagnostics
```

Inspect the most recent saved build/run report:

```bash
scpp last-run
scpp full-last-run
scpp explain-build
scpp explain-build files-transpiled
scpp explain-build entrypoint
scpp explain-build final-output
scpp explain-build ninja-target
```

Run the deterministic usability harness:

```bash
scpp usability-harness
scpp usability-harness --all
scpp usability-harness --kind scenario
scpp usability-harness --campaign scenarios_multifile
scpp usability-harness --template scenario_bool_null_gate_001
```

Current public build shape:

Compiler selection:

- persistent project setting in `prism.json`:
  - `"build": { "cxx": "clang++" }`
  - `"build": { "cxx": "g++" }`
- one-off override for a single build:
  - `SCPP_CXX=clang++ scpp build`
  - `SCPP_CXX=g++ scpp build`

`SCPP_CXX` takes precedence over `prism.json`.

- one project config: `prism.json`
- one entrypoint first
- generated C++ kept on disk
- Ninja invoked automatically by `scpp build` and `scpp run`
- output executable written under `.prism/build/`
- recursive S2S generation for all project `*.phs` files plus compatible `*.php` files
- same-project and project-dependency composition is handled by `scpp build`; do not use source-level `require`, `require_once`, `include`, or `include_once` for current strict-project composition
- cached S2S state in `.prism/cache/s2s_state.php` using file size + mtime
- `scpp build` and `scpp run` reuse existing runtime and Prism project dependency artifacts by default
- `--build-runtime` explicitly recompiles the runtime artifact for the current build
- `--build-dependencies` explicitly recompiles Prism project dependency units for the current build
- `--force` forces a runtime rebuild for the current build, even if the reusable artifact already exists
- `scpp docs <name>` prints curated local Markdown documentation by short name

## 4. Single-file transpile remains available

```bash
scpp input.phs
```

This still prints generated C++ to stdout and remains useful for narrow fixture work.

## 5. Current boundary

The project command shape is now fixed around `scpp init` + `scpp build` / `scpp run`, but the full deliberate multi-file semantic model is not complete yet. `scpp build` and `scpp run` recursively transpile project PHP++ files and compatible `.php` inputs, use cached file metadata, and still rely on the configured single entrypoint plus the repo runtime. Runtime and dependency artifacts are reused by default, with explicit rebuild flags available when you want a heavier pass.

## 6. AI onboarding

For Codex or other assistant-oriented repo guidance, start with:

- `docs/ai_onboarding/README.md`
