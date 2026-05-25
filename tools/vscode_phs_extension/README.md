# VS Code PHS Extension

Purpose: dedicated home for the Visual Studio Code extension work for PHP++ / PHS strict-mode authoring.

This folder exists to keep editor integration work separate from:

- language semantics under `specs/`
- PHP lowering under `generators/php/`
- runtime behavior under `runtime/`
- CLI and project workflow implementation under `bin/`

## Initial Focus

Phase 1 aims to provide the editing basics for `.phs` files:

- language registration
- syntax highlighting
- snippets
- simple workflow commands

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
