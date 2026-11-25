# JSS Unsupported-Syntax Diagnostics Work Note
Doc Status: planning

Date: 2026-06-12

Purpose: separate the ongoing JSS unsupported-syntax diagnostic cleanup from language-surface feature work so P1 feature implementation can proceed without mixing parser-diagnostic polish into every change set.

This is a planning artifact, not semantic authority.

## Scope

This work note covers:

- parser diagnostics for unsupported or rejected JSS syntax
- source ranges and token locations on those diagnostics
- consistency between dedicated unsupported-syntax diagnostics and generic parser fallthrough diagnostics
- focused regression tests for those diagnostics

This work note does not own:

- whether a feature is supported at all
- STAN type/classification semantics for supported features
- runtime/helper behavior
- P1 feature implementation such as `??`, ternary, result wrappers, or runtime modules

## Why Split It Out

The diagnostics pass has been useful, but it has become a separate stream of work:

- it improves user-facing quality even when language coverage does not change
- it often touches parser edges rather than language semantics
- it can obscure feature-focused diffs if it stays bundled into every JSS implementation slice

Keeping it separate should make future merges easier and keep feature branches smaller.

## Current State

As of 2026-06-12, focused JSS tests now assert source locations across a broad set of parser, semantic-validator, and classified-emitter failures, including:

- dynamic `this` and prototype rejection
- JS `import` / `export` rejection
- class visibility guardrails
- constructor/function signature guardrails
- local `const` restrictions
- bool-only condition/logical diagnostics
- typed foreach source diagnostics
- typed initialization/null/object/array-literal diagnostics
- destructuring, spread/rest, arrow syntax, template-literal interpolation limits, and object-literal parser failures

One known consistency gap remains:

- grouped arrow syntax such as `let fn = (value) => value;` currently fails through a declaration-boundary parser error (`Expected ; after JSS declaration`) instead of the dedicated unsupported-arrow diagnostic

## Next Diagnostic Tasks

1. Normalize grouped-arrow parsing so arrow-shaped syntax reports the dedicated unsupported-arrow diagnostic instead of a generic parser boundary failure.
2. Audit any remaining generic `Unexpected JSS token ...` parser failures that should become explicit unsupported-syntax diagnostics.
3. Expand location coverage from line/column-only checks toward broader source-range metadata where the parser/AST already carries it.
4. Keep unsupported-syntax tests in the focused JSS frontend suite, but avoid bundling this work with unrelated P1 feature implementation unless a feature directly changes the parser path.

## Coordination Rule

When a P1 language feature branch changes unsupported-syntax diagnostics only incidentally, keep the diagnostic adjustment minimal and leave broader normalization to this separate work note.
