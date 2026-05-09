# Workflows
Doc Status: supporting

Purpose: tell an AI assistant how to approach common work in this repo without crossing ownership boundaries.

## 1. Default Task Routing

### If the task changes user-visible language meaning

Start with:

- `specs/spec_map.md`
- the relevant top-level semantic spec under `specs/`

Then:

- update the owning spec first if meaning is unclear or changing
- align generator/runtime code after the semantic contract is clear

### If the task changes PHP lowering

Start with:

- `generators/php/specs/rules.md`
- the relevant generator sample or fixture
- generator implementation under `generators/php/src/`

Then validate with focused transpilation and the nearest tests.

### If the task changes runtime behavior

Start with:

- `runtime/specs/spec.md`
- the relevant runtime subsystem/operator spec
- the owning runtime header/source

Then validate with runtime tests, and check whether the operator matrix or config needs synchronization.

### If the task changes project workflow or build behavior

Start with:

- `specs/project_build_v1.md`
- `docs/getting_started.md`
- `bin/scpp.phs`
- `bin/project_services.phs`

Then validate through `scpp init`, `scpp build`, or `scpp run` as appropriate.

When the task touches multi-project composition, also check:

- `dependencies` versus `libraries` usage in `prism.json`
- whether dependency-visible declarations are intentionally marked with `/** @lib-export */`
- whether the change belongs in project build orchestration or in generator export/header composition

Quick shape to keep in mind:

```php
/** @lib-export */
function shared_value(): int { return 7; }
```

```json
{
  "dependencies": [
    "../shared"
  ]
}
```

```php
echo shared_value(), "\n";
```

### If the task is unclear or cross-cutting

Use this triage order:

1. identify the user-visible behavior in the top-level spec
2. identify the lowering rule in generator specs
3. identify the runtime/helper owner
4. inspect current tests/examples

This avoids fixing symptoms in the wrong layer.

## 2. What Not To Do

- Do not invent semantics from current generated C++ alone.
- Do not patch generated C++ and stop there if the real fix belongs in source specs, generator code, or runtime code.
- Do not treat planning documents as if they override normative specs.
- Do not silently broaden PHP support for unsupported constructs.
- Do not use implementation quirks as evidence that a feature is part of the intended language subset.

## 3. Validation Ladder

Use the smallest proof that matches the layer of the change.

### Generator-focused proof

Use when changing lowering or syntax support:

- focused transpilation through `scpp <file.phs>` or generator scripts
- staged samples under `generators/php/samples/`
- `know_how` fixtures when AST/exporter reality matters
- nearby tests under `tests/`

### Project workflow proof

Use when changing project mode or multi-file behavior:

- `scpp init`
- `scpp build`
- `scpp run`

Relevant docs:

- `docs/getting_started.md`
- `specs/project_build_v1.md`

### Usability proof

Use when a change may affect first-time-user authoring patterns:

- `scpp usability-harness`

See:

- `tools/usability_harness/README.md`

### Runtime proof

Use when changing runtime helpers, operators, ownership, or module behavior:

- `php tests/tools/run_tests.phs run --suite=runtime --jobs=12`
- sanitizer or gate runs when the change touches memory/ownership-sensitive paths

See:

- `tests/tools/README.md`

## 4. Practical Editing Heuristics

### For `mixed` or typed-boundary issues

Check in this order:

1. `specs/dynamic_types.md`
2. the source pattern that creates the boundary
3. `generators/php/specs/rules.md`
4. `generators/php/src/Lowering/TypeMapper.phs`
5. runtime mixed helpers

### For array/table issues

Check in this order:

1. `specs/array_semantics.md`
2. `specs/count_empty_isset_contract.md`
3. generator lowering for dim reads/writes
4. runtime table/hash operators

### For conditions, `isset`, `empty`, `unset`, or `??`

Check:

- the owning top-level semantic spec
- operator/runtime docs under `runtime/specs/`
- `runtime/include/operators/`

### For builtins

Check:

- `specs/builtins/`
- matching docs under `docs/` when present
- runtime module/language-layer implementation

### For project command behavior

Check:

- `specs/project_build_v1.md`
- `bin/project_services.phs`
- `docs/getting_started.md`
- current `prism.json` expectations

## 5. Generated Code Policy

Treat generated C++ as:

- a debugging surface
- evidence of current lowering
- a way to inspect runtime helper selection

Do not treat it as:

- the primary source to edit for language changes
- a stable long-term semantic document

If generated output looks wrong, fix the owning spec/generator/runtime layer rather than normalizing the broken output by hand.

Generated output can still be edited deliberately when:

- the task is about native runtime support code
- the task is about build integration or handwritten `native_cpp/`
- the task is debugging emitted output and the real follow-up fix is tracked back to its owner

## 6. AI Writing Style Expectations

When editing source-language examples or user-facing PHP:

- prefer strict comparisons
- keep null and false distinct
- prefer explicit typed boundaries when they improve clarity
- avoid implicit truthiness when intent is ambiguous
- prefer predictable, deterministic code patterns over clever PHP shortcuts
