Doc Status: planning

# VS Code PHS Extension Phase 1

## Purpose

Define the initial repository shape and scope for a Visual Studio Code extension that supports PHP++ / PHS strict-mode authoring in `.phs` files.

This note is a planning document only. It does not define language semantics and does not override normative specs.

## Background

The repository already establishes that:

- `.phs` is the canonical source extension for PHP++ / PHS strict projects
- the surface syntax is PHP-like but must not be treated as full PHP
- the current generator is a deterministic lowerer, not a full semantic compiler

That means the first VS Code integration should focus on editor basics and avoid implying deeper semantic understanding than the current tooling actually provides.

## Phase 1 Goal

Create a clean, separate workspace for a first VS Code extension pass that provides a usable editing experience for `.phs` files without depending on a full analyzer-backed language service.

## Phase 1 Scope

Phase 1 should include:

- VS Code language registration for `.phs`
- basic editor configuration such as comments, bracket pairs, and file associations
- syntax highlighting suitable for PHP++ / PHS strict authoring
- snippets for common strict-mode patterns
- simple extension commands for project workflows such as build, run, doctor, and docs

Phase 1 may also include:

- static completion for language keywords
- static completion for known strict-profile builtins and common library symbols when sourced from stable repository metadata

## Explicit Non-Goals For Phase 1

Phase 1 should not require:

- a full language server
- analyzer-driven member completion
- project-wide symbol resolution
- hover, definition, reference, or rename intelligence based on semantic analysis
- pretending that PHP tooling for normal PHP is semantically correct for PHP++ / PHS strict mode

## Repository Layout

The work should live in a dedicated tools-owned area so it stays separate from generator, runtime, and CLI implementation work.

Initial home:

- `tools/vscode_phs_extension/`

Suggested internal structure:

- `tools/vscode_phs_extension/README.md`
- `tools/vscode_phs_extension/extension/`
- `tools/vscode_phs_extension/docs/`
- `tools/vscode_phs_extension/testdata/`

The exact subfolders can evolve, but the top-level tooling home should remain dedicated to the VS Code extension effort.

## Chosen Identity

The initial extension identity for this workstream is:

- extension display name: `Simple C++`
- package name: `simple-cpp-vscode`
- language id: `phs`
- language display name: `PHP++ (PHS)`
- primary file extension: `.phs`

## Design Principles

### 1. Keep the language identity explicit

The extension should treat `.phs` as its own authoring surface rather than as ordinary PHP with a renamed file extension.

### 2. Prefer repo-owned metadata where possible

If Phase 1 needs builtin or keyword catalogs, prefer deriving them from repository-owned specs or config rather than maintaining a disconnected editor-only list when practical.

### 3. Keep semantic intelligence out of the editor shell

If later phases add project-aware completion, hover, or navigation, that intelligence should come from repository-owned analyzer tooling rather than ad hoc JavaScript-only logic inside the VS Code extension.

### 4. Ship a useful first pass early

The first extension pass should prioritize correctness of the editing surface and a clean user experience over ambitious semantic features.

## Likely Phase 2 Direction

Later phases can integrate a static analyzer or language-service process for:

- project symbol completion
- hover details
- go to definition
- workspace symbols
- richer diagnostics

That analyzer does not need to be written in JavaScript. A thin VS Code extension can call an external tool or speak LSP to a non-JavaScript backend.

## Immediate Next Steps

1. Create the dedicated `tools/vscode_phs_extension/` folder.
2. Add a small README that defines the folder purpose and initial boundaries.
3. Decide the extension package name, language id, and visible display name.
4. Scaffold the Phase 1 extension layout inside the dedicated folder.
