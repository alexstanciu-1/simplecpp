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

`scpp update` fetches GitHub `origin/main` and fast-forwards the checkout. It requires the checkout to be on `main` with no local changes.

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
```

Pass program arguments after `--`:

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
- cached S2S state in `.prism/cache/s2s_state.php` using file size + mtime

## 4. Single-file transpile remains available

```bash
scpp input.phs
```

This still prints generated C++ to stdout and remains useful for narrow fixture work.

## 5. Current boundary

The project command shape is now fixed around `scpp init` + `scpp build` / `scpp run`, but the full deliberate multi-file semantic model is not complete yet. `scpp build` and `scpp run` recursively transpile project PHP++ files and compatible `.php` inputs, and use cached file metadata, while still relying on the configured single entrypoint and the repo runtime directly.

## 6. AI onboarding

For Codex or other assistant-oriented repo guidance, start with:

- `docs/ai_onboarding/README.md`
