Doc Status: planning

# STAN LSP Handoff

Date: 2026-05-25
Branch: `feature/stan-advisory-semantic-pass`
Latest commit at handoff: `36d71bf` `Add STAN LSP server scaffold and VS Code smoke flow`

## Purpose

This note is a short handoff for the next chat so work can continue without re-discovery.

## Current Outcome

STAN is no longer only a CLI advisory analyzer. It now has:

- a persistent LSP-style server entrypoint
- workspace-session analysis with source overrides
- editor-facing diagnostics, hover, definition, references, and document symbols
- unsaved-buffer overlay handling
- publish-diagnostics notifications
- a VS Code client scaffold
- a bundled smoke workspace for manual testing

## Main Files

Server / STAN bridge:

- [bin/stan_lsp_server.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/stan_lsp_server.php)
- [bin/project_services.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/bin/project_services.php)
- [generators/php/src/Stan/StanLspServerSession.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanLspServerSession.php)
- [generators/php/src/Stan/StanWorkspaceSession.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanWorkspaceSession.php)
- [generators/php/src/Stan/StanPositionResolver.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanPositionResolver.php)
- [generators/php/src/Stan/StanResultAssembler.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanResultAssembler.php)

Session / context / source-unit layer:

- [generators/php/src/Stan/StanWorkspaceContext.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanWorkspaceContext.php)
- [generators/php/src/Stan/StanWorkspaceContextBuilder.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanWorkspaceContextBuilder.php)
- [generators/php/src/Stan/StanSourceUnit.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanSourceUnit.php)
- [generators/php/src/Stan/StanSourceCatalogBuilder.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanSourceCatalogBuilder.php)
- [generators/php/src/Stan/StanSourceMetaBuilder.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanSourceMetaBuilder.php)

Diagnostics / tests:

- [generators/php/src/Stan/StanDiagnosticEnricher.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/generators/php/src/Stan/StanDiagnosticEnricher.php)
- [tests/tools/test_scpp_stan_diagnostics_session.php](/home/alexv/__AI/simple_cpp/simple_cpp_02/tests/tools/test_scpp_stan_diagnostics_session.php)

VS Code scaffold:

- [tools/vscode-stan-lsp/package.json](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/package.json)
- [tools/vscode-stan-lsp/extension.js](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/extension.js)
- [tools/vscode-stan-lsp/README.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/README.md)
- [tools/vscode-stan-lsp/.vscode/launch.json](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/.vscode/launch.json)
- [tools/vscode-stan-lsp/smoke-workspace.code-workspace](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace.code-workspace)
- [tools/vscode-stan-lsp/smoke-workspace/main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace/main.phs)

## What Works

### STAN analyzer

- advisory run on real projects
- per-file cache reuse
- project-wide symbol indexing
- duplicate declaration detection
- declaration-backed type extraction
- chain/member/property/call diagnostics
- local flow and no-morphing checks

### LSP-style server

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

### Important bridge behaviors

- unsaved-buffer overlays are supported
- request-level snapshot reuse exists
- `_debug` metadata exists for one-shot and serve-mode inspection
- standard `textDocument/*` aliases return LSP-shaped payloads
- legacy `stan/*` methods still exist for internal/dev use

### VS Code scaffold

- launches one client per workspace folder
- has WSL-aware server launch logic for `\\wsl$\...` workspaces
- uses a smoke workspace for manual testing
- in development mode it tries to auto-open `smoke-workspace/main.phs`
- has a manual command: `Simple C++: Open Smoke File`

## Important Fixes Already Made

### 1. Startup crash fix

The earlier server crash was caused by assuming startup `cwd` already contained `prism.json`.

This is fixed by:

- lazy project initialization from `rootUri` / `workspaceFolders` / document URIs
- removing the hard dependency on startup `getcwd()`

### 2. WSL / Windows launch hardening

The VS Code scaffold now detects `\\wsl$\...` workspace paths and tries to launch the server via:

- `wsl.exe -d <distro> --cd <linux-workspace> php <linux-server-script>`

instead of relying on Windows PHP + UNC path startup.

### 3. Usage-site navigation improvement

`definition` and `references` are no longer limited to declaration lines only.

Current behavior:

- exact declaration match when possible
- fallback to identifier-under-cursor
- simple textual usage-site references across indexed files

This is still conservative, but materially better than declaration-only matching.

## Validation Done

### Core regression test

Run:

```bash
php tests/tools/test_scpp_stan_diagnostics_session.php
```

This currently covers:

- session `run`
- `runWithOverrides`
- `runDiagnostics`
- one-shot CLI bridge
- JSON-RPC bridge
- serve-mode bridge
- debug metadata hit/miss
- hover / definition / references / document symbols
- lazy server initialization from `rootUri`
- overlay lifecycle
- watched-file refresh notifications

### VS Code scaffold check

Run:

```bash
cd tools/vscode-stan-lsp
npm run check --cache /tmp/npm-simplecpp-cache
```

### Smoke workspace STAN check

Run:

```bash
php /home/alexv/__AI/simple_cpp/simple_cpp_02/bin/scpp.php stan
```

from:

```bash
/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace
```

Expected:

- `Warnings: 1`
- one local morph warning on `$value = helper();`

## Manual Smoke State

Manual testing reached this point:

- Extension Development Host opens
- smoke workspace opens
- `main.phs` can auto-open in dev mode
- startup crash path is no longer the primary blocker

What still needs manual confirmation in the next chat:

1. hover on `helper()`
2. go-to-definition on `helper()`
3. references on `helper()`
4. Problems pane showing the one expected warning
5. output channel showing healthy client/server startup

## Known Caveats

### 1. `node_modules` is committed in the VS Code scaffold

The last commit includes `tools/vscode-stan-lsp/node_modules`.

This is not ideal long-term.

Likely follow-up:

- add proper ignore rules
- remove vendored client deps from git
- rely on `npm install`

### 2. Navigation semantics are still narrow

Current references are better, but still not true semantic reference analysis.

Good next upgrade area:

- tie references more directly to semantic usage sites
- reduce textual false positives

### 3. Manual GUI verification is still partial

The server/client boot path looks much better, but full editor UX still needs human confirmation.

## Highest-Signal Next Steps

### Server / semantics

1. strengthen reference semantics from true usage sites
2. improve token-accurate hover / definition resolution
3. consider `publishDiagnostics` batching policy for larger workspaces

### VS Code client

1. visible status signal like “STAN connected”
2. clearer output-channel logging on startup
3. possibly a dev-only smoke command palette flow
4. multi-root polish beyond one-client-per-folder

### Repo hygiene

1. clean up committed `node_modules`
2. decide whether the scaffold should stay JS-only or move to TS

## Suggested Resume Prompt

When starting the new chat, something like this should be enough:

> Continue from `specs/planning/stan_lsp_handoff_2026_05_25.md`.  
> Focus first on confirming the VS Code smoke pass and then improve navigation/diagnostics behavior.

