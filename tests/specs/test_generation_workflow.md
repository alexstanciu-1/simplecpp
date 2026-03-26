# Simple C++ – Test Generation Workflow

This file is a focused workflow companion to `generate_php_samples_docs.md`.

## Workflow

1. read normative specs
2. read support/config specs
3. extract candidate obligations
4. normalize and deduplicate
5. classify by origin and obligation type
6. map to levels and outcome classes
7. generate concrete PHP files and sidecar JSON
8. review for gaps, regressions, and filler

## Minimum mapping

For each required supported feature/design target:
- create one `level_01` positive sample
- create one nearby boundary or negative sample

## Expand to level_02 when

- multiple branches exist
- multiple type variants exist
- failure modes exist
- regression history exists

## Expand to level_03 when

- feature interaction is the subject
- generator + runtime interaction must be validated
- historical combination failures exist


## Current extraction rules

During obligation extraction and sample planning, the generator MUST apply the following current project decisions:

1. Treat `[]` and `[ ... ]` as vector construction only when an explicit `/** vector<T> */` context exists.
2. Reject untyped array literals from the active supported suite; reserve them for future PHP-like-array work.
3. Allow mixed-type and nested explicit vector literals to become compile-stage obligations when the generator intentionally defers type enforcement to C++.
4. Treat `foreach` as vector-only.
5. Treat `foreach ($v as $item)` and `foreach ($v as &$item)` as supported.
6. Treat `foreach ($v as $k => $item)` and `foreach ($v as $k => &$item)` as unsupported.
7. Treat foreach variables as inner-scope loop locals for current supported semantics.
8. Generate interpolation tests as planned `known_gap` items until implementation exists, while already using the final intended interpolation surface and cast-to-string rule.


## Locked phase-1 execution plan

The current phase-1 execution plan is:
1. lock the language-area tree in docs
2. generate actual `level_01` files first
3. review those files
4. expand to `level_02` and `level_03` after review

The current `level_01` generation pass MUST cover these language areas:
- types (`int`, `float`, `bool`, `string`, `nullable`, `vector`)
- variables
- constants
- operators
- casts
- control_flow
- functions
- classes
- namespaces
- use
- references
- output
- known-gap interpolation planning samples
