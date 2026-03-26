# Simple C++ – Test Source Hierarchy

This file is a focused companion to `generate_php_samples_docs.md`.

## Normative order

Use this precedence order when deciding what tests should exist:

1. `runtime/specs/spec.md`
2. `php_generator/specs/rules.md`
3. `php_generator/specs/rules_catalog.md`
4. `runtime/specs/config.json`
5. implementation code
6. regression history

## Interpretation

- tiers 1 to 3 define intended behavior
- tier 4 refines support boundaries
- tiers 5 and 6 add regression and mismatch coverage

## Conflict handling

When sources disagree, do not silently choose.
Create and track one of:
- `spec_gap`
- `known_fail`
- `regression`


## Locked interpretation notes for current generation

The following current decisions are normative for test generation even if implementation behavior is temporarily looser:
- array literals create `vector<T>` only in explicit `/** vector<T> */` contexts
- untyped `[]` and untyped `[1, 2, 3]` are rejected for now and reserved for future PHP-like-array support
- `foreach` is vector-only in v1 test generation
- `foreach` key/value forms are unsupported
- `foreach` loop variables use inner C++-like scope for current supported semantics
- string interpolation is spec-supported but implementation-missing, therefore generated as `known_gap` / `todo` until support lands


## Phase-1 tree note

After obligation extraction, current sample planning MUST map the approved phase-1 language areas into the tree documented in `generate_php_samples_docs.md`, with actual file generation starting from `level_01`.
