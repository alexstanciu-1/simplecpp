# Simple C++ VS Code Extension

This folder contains the current `Simple C++` Visual Studio Code extension workspace.

## Identity

- package name: `simple-cpp-vscode`
- extension display name: `Simple C++`
- language id: `phs`
- language display name: `PHP++ (PHS)`

## Phase 1 Scope

Current functionality provides:

- project creation through `scpp init`
- `.phs` language registration
- editor language configuration
- starter syntax highlighting
- strict-mode snippets
- simple `scpp` workflow commands
- context-aware static completion for keywords, types, builtins, and visible variables
- STAN-backed diagnostics, hover, definition, references, and document symbols
- preview debug inspection commands and a minimal debug-adapter scaffold for Simple C++ debug sessions

Later phases can still add richer analyzer-backed completion and deeper semantic editing features.

## Layout

- `package.json`: VS Code extension manifest
- `language-configuration.json`: brackets, comments, and editor behavior
- `syntaxes/`: TextMate grammar
- `snippets/`: snippet catalog
- `src/`: lightweight extension host code
- `smoke-workspace.code-workspace`: debug workspace for STAN smoke testing
- `docs/`: local load/test notes

## Local Development

- open this folder in VS Code
- press `F5` to launch an Extension Development Host
- use `Ctrl+Space` in a `.phs` file to test static completion
- use the bundled smoke workspace to test STAN diagnostics and navigation

Helpful files:

- `jsconfig.json`: basic JavaScript project settings for the extension host files
- `.vscodeignore`: lightweight packaging filter for future `.vsix` creation
- `.vscode/launch.json`: `F5` debug config for launching an Extension Development Host
- `.vscode/tasks.json`: small helper tasks for local extension checks

Helpful script:

- `npm run check`
- `npm run package:check`

That packaging helper expects `npx` access to `@vscode/vsce` in the local environment.

## Debugging

The repository includes a ready-to-use VS Code debug configuration.

1. Open this extension folder in VS Code.
2. Press `F5`.
3. Choose `Run Simple C++ Extension` if prompted.
4. VS Code should open an Extension Development Host window on the bundled smoke workspace.

## Debug Preview

The extension now includes a lightweight debug-preview layer behind the scenes.

Current preview commands:

- `Simple C++: Inspect Latest Debug Session`
- `Simple C++: Inspect Debug Slots`

These commands can surface saved debug artifacts from `.prism/debug/index.json`, including:

- saved `events.json`
- saved `plan.json`
- rewritten debug source
- rewritten-source line maps

The extension also includes a minimal `simplecpp-debug` debug type scaffold intended for early launch/breakpoint/stop testing against `scpp debug`.
Treat that debug adapter as preview infrastructure, not a finished debugger UI.
