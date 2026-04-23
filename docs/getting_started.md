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

## 2. Start a project

From the project root:

```bash
scpp init
```

This creates:

- `prism.json`
- `.prism/build/`
- `.prism/generated/`
- `.prism/cache/`

`scpp init` first looks for a common non-web-first entrypoint such as:

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
- recursive S2S generation for all project `*.php` files
- cached S2S state in `.prism/cache/s2s_state.php` using file size + mtime

## 4. Single-file transpile remains available

```bash
scpp input.php
```

This still prints generated C++ to stdout and remains useful for narrow fixture work.

## 5. Current boundary

The project command shape is now fixed around `scpp init` + `scpp build` / `scpp run`, but the full deliberate multi-file semantic model is not complete yet. `scpp build` and `scpp run` recursively transpile project PHP files and use cached file metadata, while still relying on the configured single entrypoint and the repo runtime directly.

## 6. AI onboarding

For Codex or other assistant-oriented repo guidance, start with:

- `docs/ai_onboarding/README.md`
