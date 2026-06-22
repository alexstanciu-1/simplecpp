# VS Code Simple C++ Extension

Purpose: dedicated home for the Visual Studio Code extension work for PHP++ / PHS strict-mode authoring and the JSS v1-alpha typed script-style frontend.

This folder exists to keep editor integration work separate from:

- language semantics under `specs/`
- PHP lowering under `generators/php/`
- runtime behavior under `runtime/`
- CLI and project workflow implementation under `bin/`

## Initial Focus

Phase 1 aims to provide the editing basics for `.phs` and `.jss` files:

- language registration
- syntax highlighting
- snippets
- simple workflow commands
- JSS reserved helper-family completion for `fs`, `io`, `json`, and `dt`

Semantic editor features should be layered in later through repository-owned analyzer tooling rather than ad hoc editor-only logic.

## Current Static Metadata Source

Phase 1 static builtin completion should prefer repository-owned metadata from:

- `generators/php/specs/php_runtime_symbols_strict.json`

That keeps the first editor completions aligned with the strict runtime symbol registry already tracked in the repository.

## Expected Growth

This folder may later contain:

- extension source
- packaging files
- test fixtures
- editor-specific docs
