# Simple C++ VS Code Extension

This folder contains the Phase 1 scaffold for the `Simple C++` Visual Studio Code extension.

## Identity

- package name: `simple-cpp-vscode`
- extension display name: `Simple C++`
- language id: `phs`
- language display name: `PHP++ (PHS)`

## Phase 1 Scope

Phase 1 provides:

- `.phs` language registration
- editor language configuration
- starter syntax highlighting
- strict-mode snippets
- simple `scpp` workflow commands
- context-aware static completion for keywords, types, builtins, and visible variables

Later phases can add analyzer-backed completion, hover, definitions, symbols, and richer diagnostics.

## Layout

- `package.json`: VS Code extension manifest
- `language-configuration.json`: brackets, comments, and editor behavior
- `syntaxes/`: TextMate grammar
- `snippets/`: snippet catalog
- `src/`: lightweight extension host code
- `docs/`: local load/test notes

## Local Development

- open this folder in VS Code
- press `F5` to launch an Extension Development Host
- use `Ctrl+Space` in a `.phs` file to test static completion

Helpful files:

- `jsconfig.json`: basic JavaScript project settings for the extension host files
- `.vscodeignore`: lightweight packaging filter for future `.vsix` creation

Helpful script:

- `npm run package:check`

That packaging helper expects `npx` access to `@vscode/vsce` in the local environment.
