Doc Status: planning

# STAN Handoff

Date: 2026-05-25
Branch: `main`
Latest checked commit during handoff refresh: `1b12c55`

## Purpose

This note is the restart point for the next chat.

It now covers both:

- STAN as VS Code/editor support
- STAN as `scpp build` pre-build support

This is planning/handoff context only.
It is not semantic authority.

## Current Outcome

STAN is no longer only a one-shot CLI advisory analyzer.

It now has:

- a working LSP-style server path
- VS Code integration merged into the PHS extension
- editor-facing diagnostics, hover, definition, references, and document symbols
- a multi-file smoke workspace for manual editor testing
- a per-project STAN worker mode
- project-local STAN status/report/request/heartbeat files
- `scpp build` pre-build STAN consultation
- a conservative `compile-errors` bucket that can stop builds early
- a `--no-stan` bypass for early-release escape-hatch use

## Release State

The STAN/editor/build integration shipped in release `0.1.62`.

Published artifacts/state:

- release PR to `main`: `#173`
- release-sync PR back to `develop`: `#174`
- published tag: `v0.1.62`
- published GitHub release:
  - [v0.1.62](https://github.com/alexstanciu-1/simplecpp/releases/tag/v0.1.62)

Branch/result summary:

- `main` contains the merged `0.1.62` release result
- GitHub `develop` was synchronized from `main` through PR `#174`
- the temporary `release/v0.1.62` branch has been removed on GitHub

## Main Files

### CLI / build / worker integration

- [bin/project_services.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/project_services.php)
- [bin/stan_lsp_server.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/stan_lsp_server.php)
- [specs/planning/stan_build_worker_integration_plan_2026_05_25.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/specs/planning/stan_build_worker_integration_plan_2026_05_25.md)

### STAN core / session / semantics

- [generators/php/src/Stan/StanRunner.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanRunner.php)
- [generators/php/src/Stan/StanWorkspaceSession.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanWorkspaceSession.php)
- [generators/php/src/Stan/StanWorkspaceContext.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanWorkspaceContext.php)
- [generators/php/src/Stan/StanWorkspaceContextBuilder.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanWorkspaceContextBuilder.php)
- [generators/php/src/Stan/StanResultAssembler.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanResultAssembler.php)
- [generators/php/src/Stan/StanSemanticPass.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanSemanticPass.php)
- [generators/php/src/Stan/StanDiagnosticCollector.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanDiagnosticCollector.php)
- [generators/php/src/Stan/StanDiagnosticEnricher.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanDiagnosticEnricher.php)
- [generators/php/src/Stan/StanExpressionTypeResolver.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanExpressionTypeResolver.php)
- [generators/php/src/Stan/StanStateStore.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanStateStore.php)
- [generators/php/src/Stan/StanLspServerSession.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanLspServerSession.php)
- [generators/php/src/Stan/StanPositionResolver.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanPositionResolver.php)
- [generators/php/src/Stan/StanSymbolIndexBuilder.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanSymbolIndexBuilder.php)

### VS Code extension

- [tools/vscode_phs_extension/extension/package.json](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/extension/package.json)
- [tools/vscode_phs_extension/extension/src/extension.js](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/extension/src/extension.js)
- [tools/vscode_phs_extension/extension/README.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/extension/README.md)
- [tools/vscode_phs_extension/extension/smoke-workspace.code-workspace](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/extension/smoke-workspace.code-workspace)
- [tools/vscode_phs_extension/testdata/stan_smoke_workspace/main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/testdata/stan_smoke_workspace/main.phs)
- [tools/vscode_phs_extension/testdata/stan_smoke_workspace/lib/helpers.phs](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/testdata/stan_smoke_workspace/lib/helpers.phs)
- [tools/vscode_phs_extension/testdata/stan_smoke_workspace/lib/models.phs](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/testdata/stan_smoke_workspace/lib/models.phs)

### Tests

- [tests/tools/test_scpp_stan_diagnostics_session.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/tests/tools/test_scpp_stan_diagnostics_session.php)
- [tests/tools/test_scpp_stan_build_worker_integration.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/tests/tools/test_scpp_stan_build_worker_integration.php)
- [tests/tools/test_scpp_build_reuse_integration.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/tests/tools/test_scpp_build_reuse_integration.php)
- [tests/tools/test_scpp_build_options.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/tests/tools/test_scpp_build_options.php)

## What Works

### STAN analyzer

- advisory run on real projects
- per-file cache reuse
- project-wide symbol indexing
- duplicate declaration detection
- declaration-backed type extraction
- chain/member/property/call diagnostics
- local flow and no-morphing checks

### LSP/editor support

- `initialize`, `shutdown`, `exit`
- `textDocument/diagnostic`
- `textDocument/documentSymbol`
- `textDocument/hover`
- `textDocument/definition`
- `textDocument/references`
- `textDocument/didOpen`
- `textDocument/didChange`
- `textDocument/didClose`
- `textDocument/didSave`
- `workspace/didChangeWatchedFiles`
- `textDocument/publishDiagnostics`
- true stdio LSP framing with `Content-Length`
- unsaved-buffer overlays
- request-level snapshot reuse

### VS Code integration

- extension now lives in `tools/vscode_phs_extension/extension`
- STAN client is merged into the PHS extension
- syntax highlighting and static completion still exist
- WSL workflow works from Windows VS Code with `Remote - WSL`
- diagnostics / definition / references / hover were manually exercised
- hover now includes proper function/method signatures for smoke cases
- duplicate hover diagnostics were cleaned up
- `Simple C++: Create Project`
- `Simple C++: Build Project`
- `Simple C++: Run Project`
- `Simple C++: Restart STAN Server`
- `Simple C++: Open Smoke File`

### Build integration

- `scpp stan worker`
- project-local STAN files under `.prism/cache/`:
  - `stan_status.json`
  - `stan_report.json`
  - `stan_worker.json`
  - `stan_request.json`
- `scpp build` now consults STAN before continuing
- if no live worker exists, build does an inline STAN refresh and publishes the same files
- if a live worker exists, build requests refresh and waits briefly for fresh state
- conservative `compile-errors` can stop builds before later C++ compiler failure
- advisory findings only print as summary during build
- `scpp build --no-stan` bypass exists
- `scpp run --no-stan` also works through the shared build-option path

## Important Fixes Already Made

### 1. LSP transport fix

The server originally read newline JSON only.

This is fixed by:

- proper LSP stdio framing support in the STAN serve loop
- compatibility with the legacy newline-based internal test path

### 2. Startup initialization fix

The earlier server crash assumed startup `cwd` already contained `prism.json`.

This is fixed by:

- lazy project initialization from `rootUri` / `workspaceFolders` / document URIs
- removing the hard dependency on startup `getcwd()`

### 3. WSL / Windows launch hardening

The VS Code client handles WSL-aware launch better.

The recommended workflow is:

- Windows VS Code
- `Remote - WSL`
- open repo from WSL with `code .`

### 4. Usage-site navigation / hover improvement

`definition`, `references`, and `hover` no longer work only on declaration lines.

Current behavior:

- exact declaration match when possible
- fallback to identifier-under-cursor
- signature-aware hover for smoke-workspace functions/methods

### 5. STAN build preflight shape

The current build preflight avoids unnecessary worker churn:

- if there is no live worker, build runs STAN inline
- if there is a live worker, build reuses/request-polls that worker

This removed the earlier temp-project cleanup race and unnecessary slowdown from build-triggered worker spawning.

## Current Build-Bucket Policy

The first conservative mapping is:

### `compile-errors`

- `duplicate_declaration`
- `unresolved_call`
- `unresolved_static_call`
- `unresolved_method_call`
- `unresolved_property_write`
- `unresolved_property_read`

### `stan-errors`

- `unresolved_dependency`
- `ambiguous_dependency`
- `override_declaration`
- `argument_type_mismatch`
- `argument_count_mismatch`
- `static_instance_misuse`
- `missing_return`
- `invalid_property_read`

### `stan-warnings`

- current warning-grade findings such as:
  - `local_type_morph_warning`
  - `property_type_morph_warning`
  - `initialization_warning`
  - chain-resolution warning families

This mapping is intentionally narrow for early release.

## Validation Done

### Core STAN/LSP regression

Run:

```bash
php tests/tools/test_scpp_stan_diagnostics_session.php
```

This covers:

- session `run`
- `runWithOverrides`
- `runDiagnostics`
- one-shot CLI bridge
- JSON-RPC bridge
- serve-mode bridge
- hover / definition / references / document symbols
- lazy server initialization from `rootUri`
- overlay lifecycle
- watched-file refresh notifications

### STAN build-worker regression

Run:

```bash
php tests/tools/test_scpp_stan_build_worker_integration.php
```

This covers:

- advisory findings summarized during build
- build-blocking STAN compile-errors
- project-local STAN report/state publication
- `--no-stan` bypass behavior

### Broader build regression

Run:

```bash
php tests/tools/test_scpp_build_reuse_integration.php
php tests/tools/test_scpp_build_options.php
```

These currently pass with the new STAN preflight in place.

## Manual Smoke State

Manual VS Code smoke reached a good point:

- `Remote - WSL` workflow is working
- extension host launches
- smoke workspace opens
- `.phs` recognition works
- diagnostics appear in Problems
- definition works
- references works
- hover works and now includes signature data
- `Simple C++: Run Project` executes in WSL

One subtle point already clarified for the user:

- `F5` in the extension project window launches the extension host
- `F5` inside the Extension Development Host tries to debug the open `.phs` file, which is not the intended run path

The intended run path in the dev host is:

- command palette commands like `Simple C++: Run Project`

## Known Caveats

### 1. Compile-error mapping is intentionally incomplete

The current mapping is conservative on purpose.

It is good for early release but not exhaustive.

### 2. `scpp stan` warm-report path is summary-oriented

`scpp stan` can now reuse the warm report when the source fingerprint matches, but the user-facing output is still the traditional summary lines rather than a richer bucket-focused display.

### 3. Worker watch model is still simple

The worker uses:

- project-local files
- polling
- lock file coordination

It is deliberately simple for now and not yet a sophisticated OS-native watcher.

### 4. Build fingerprint currently covers only root-project sources

The current STAN build fingerprint includes:

- the root project's `prism.json`
- the root project's participating `*.phs` / compatible `*.php` files

It does not yet include dependency project source fingerprints.

That is acceptable for the current early release, but QA should treat dependency-only edits as a known warm-cache freshness limitation for STAN-backed `scpp build`.

### 5. VS Code release/publish work is still separate

The extension works in dev/smoke mode.

Release packaging / listing / metadata polish is still a separate task.

## Highest-Signal Next Steps

### STAN / build

1. keep refining compile-error mapping from real diagnostic samples
2. decide whether `scpp stan` should expose build buckets directly
3. decide whether worker mode should evolve from polling to stronger file-watch behavior

### VS Code

1. more manual smoke on richer multi-file cases
2. package/release polish for the merged PHS + STAN extension
3. docs cleanup around normal-user install flow

### Repo hygiene

1. update docs/spec text to mention `--no-stan`
2. continue normalizing extension release metadata

## Suggested Resume Prompt

When starting the new chat, something like this should be enough:

> Continue from `specs/planning/stan_lsp_handoff_2026_05_25.md`.  
> STAN LSP/editor support and STAN build-worker integration are both in.  
> Please continue from the current build-bucket / VS Code release state.
