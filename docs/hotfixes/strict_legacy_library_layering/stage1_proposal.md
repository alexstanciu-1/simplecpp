# Strict vs PHP-Legacy Library Layering

Doc Status: planning

## Stage 1 Proposal

### Goal

Refactor runtime/library ownership without changing the current PHP-legacy user-visible contract.

### Scope

- define the new shared runtime API for the first selected capability family
- implement the new shared runtime API
- make the existing legacy PHP surface a thin adapter over it
- update focused docs and tests
- do not add the strict PHP surface yet
- do not split symbol catalogs yet

### Hard Rules

- no intentional change to legacy PHP-visible behavior
- no new strict/non-legacy PHP symbols in Stage 1
- no profile/catalog loading changes in Stage 1
- runtime becomes the real authority where the capability is reusable
- PHP legacy surface remains the public surface for now, but only as an adapter

### Target Outcome

After Stage 1:

- runtime owns the reusable capability
- PHP legacy API still works with the same contract
- adapter direction is corrected
- Stage 2 can add the strict PHP surface without touching runtime again

### Recommended First Slice

Use one narrow capability family first, preferably:

- directory scanning
- optionally file get/put if they are already close in implementation shape

Minimal example:

- shared runtime capability:
  - `scpp::fs::scan(path) -> result<vector_t<string_t>>`
- legacy PHP adapter:
  - `scpp::php::scandir(path) -> result_or_false<hash_t<mixed_t>>`

### Documentation Set

1. short Stage 1 design note
2. runtime ownership note
3. per-API mapping table

### Mapping Table Shape

- legacy PHP symbol
- Stage 1 runtime authority
- adapter conversion
- PHP-visible contract preserved
- notes

Example rows:

- `scpp::php::scandir`
- `scpp::fs::scan`
- `result<vector_t<string_t>> -> result_or_false<hash_t<mixed_t>>`
- `yes`
- `strict surface deferred to Stage 2`

### Validation Plan

- focused tests only
- legacy behavior regression tests for touched APIs
- runtime/unit tests for the new shared capability
- no matrix in Stage 1 unless unavoidable

### Non-Goals

- no strict PHP surface
- no `php_runtime_symbols_strict.json`
- no `php_runtime_symbols_legacy.json`
- no `scpp init` profile changes
- no broad library migration
- no JS surface work

### Exit Criteria

Stage 1 is complete when:

- selected reusable capability exists in shared runtime
- legacy PHP entrypoint delegates to it
- legacy behavior is preserved
- docs reflect new ownership
- focused tests pass
